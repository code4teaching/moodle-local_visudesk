<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.


/**
 * VisuGrade sync store: encrypted changes and snapshots per user. The server never sees content.
 *
 * @package    local_visudesk
 * @copyright  2026 Holger Sebastião
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_visudesk\local;

/**
 * Keeps the changes VisuGrade sends (encrypted on the device with a key Moodle never gets), numbers them in the order
 * they arrive, keeps one snapshot per user and relays the pairing of new devices.
 *
 * The apps apply changes in the order of their numbers. A device may only send on top of the newest number (basis);
 * otherwise it has to fetch first. Changes older than 30 days that a snapshot covers are deleted every night.
 *
 * @package    local_visudesk
 * @copyright  2026 Holger Sebastião
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class vgstore {
    /** @var string Changes. */
    const ENTRIES = 'local_visudesk_vgentry';
    /** @var string Snapshots, one per user. */
    const SNAPSHOTS = 'local_visudesk_vgsnap';
    /** @var string Pairing requests of new devices. */
    const PAIRS = 'local_visudesk_vgpair';
    /** @var int Largest change in bytes (encrypted, base64). */
    const MAXENTRY = 65536;
    /** @var int Largest snapshot in bytes. */
    const MAXSNAPSHOT = 20971520;
    /** @var int Most bytes per user (changes and snapshot). */
    const MAXUSER = 52428800;
    /** @var int Most changes in one push. */
    const MAXPUSH = 200;
    /** @var int Days changes stay (the history the apps show). */
    const KEEPDAYS = 30;
    /** @var int Minutes a pairing request stays. */
    const PAIRMINUTES = 10;
    /** @var string Device id: random hex. */
    const DEVICE = '/^[0-9a-f]{8,32}$/';
    /** @var string Encrypted payload: base64. */
    const BLOB = '/^[A-Za-z0-9+\/]+={0,2}$/';

    /**
     * Current server time in milliseconds.
     *
     * @return int
     */
    public static function now(): int {
        return (int)floor(microtime(true) * 1000);
    }

    /**
     * Checks an encrypted payload.
     *
     * @param string $data
     * @param int $max
     * @return string
     */
    public static function check_blob(string $data, int $max): string {
        if ($data === '' || strlen($data) > $max || !preg_match(self::BLOB, $data)) {
            throw new \invalid_parameter_exception('Invalid or too large payload');
        }
        return $data;
    }

    /**
     * Checks a device id.
     *
     * @param string $device
     * @return string
     */
    public static function check_device(string $device): string {
        if (!preg_match(self::DEVICE, $device)) {
            throw new \invalid_parameter_exception('Invalid device id');
        }
        return $device;
    }

    /**
     * The snapshot of a user or null.
     *
     * @param int $userid
     * @return \stdClass|null
     */
    public static function snapshot(int $userid): ?\stdClass {
        global $DB;
        return $DB->get_record(self::SNAPSHOTS, ['userid' => $userid]) ?: null;
    }

    /**
     * Highest number so far (changes or snapshot), 0 if nothing yet.
     *
     * @param int $userid
     * @return int
     */
    public static function last(int $userid): int {
        global $DB;
        $entries = (int)$DB->get_field_sql('SELECT MAX(seq) FROM {' . self::ENTRIES . '} WHERE userid = ?', [$userid]);
        $snapshot = self::snapshot($userid);
        return max($entries, $snapshot ? (int)$snapshot->seq : 0);
    }

    /**
     * Changes after a number. If the device has a gap (it was offline longer than the history, or everything was
     * deleted), the snapshot comes along and the changes after it.
     *
     * @param int $userid
     * @param int $since
     * @return array ['snapshot' => ?array, 'entries' => array, 'last' => int]
     */
    public static function fetch(int $userid, int $since): array {
        global $DB;
        $snapshot = self::snapshot($userid);
        $first = (int)$DB->get_field_sql('SELECT MIN(seq) FROM {' . self::ENTRIES . '} WHERE userid = ?', [$userid]);
        $gap = $snapshot && (int)$snapshot->seq > $since && (!$first || $first > $since + 1);
        $from = $gap ? (int)$snapshot->seq : $since;
        $entries = [];
        foreach ($DB->get_records_select(self::ENTRIES, 'userid = ? AND seq > ?', [$userid, $from], 'seq') as $r) {
            $entries[] = ['seq' => (int)$r->seq, 'device' => $r->device, 'timecreated' => (int)$r->timecreated,
                'data' => $r->data];
        }
        return [
            'snapshot' => $gap ? ['seq' => (int)$snapshot->seq, 'timecreated' => (int)$snapshot->timecreated,
                'data' => $snapshot->data] : null,
            'entries' => $entries,
            'last' => self::last($userid),
        ];
    }

    /**
     * Bytes a user keeps.
     *
     * @param int $userid
     * @return int
     */
    public static function bytes(int $userid): int {
        global $DB;
        $entries = (int)$DB->get_field_sql('SELECT SUM(' . $DB->sql_length('data') . ') FROM {' . self::ENTRIES .
            '} WHERE userid = ?', [$userid]);
        $snapshot = self::snapshot($userid);
        return $entries + ($snapshot ? strlen($snapshot->data) : 0);
    }

    /**
     * Appends changes, but only on top of the newest number.
     *
     * @param int $userid
     * @param int $basis the newest number the device knows
     * @param string $device
     * @param string[] $items encrypted changes
     * @return array ['stale' => bool, 'seqs' => int[], 'last' => int]
     */
    public static function push(int $userid, int $basis, string $device, array $items): array {
        global $DB;
        self::check_device($device);
        if (count($items) > self::MAXPUSH) {
            throw new \invalid_parameter_exception('Too many changes at once');
        }
        $size = 0;
        foreach ($items as $item) {
            self::check_blob($item, self::MAXENTRY);
            $size += strlen($item);
        }
        $transaction = $DB->start_delegated_transaction();
        $last = self::last($userid);
        if ($basis < $last) {
            $transaction->allow_commit();
            return ['stale' => true, 'seqs' => [], 'last' => $last];
        }
        if (self::bytes($userid) + $size > self::MAXUSER) {
            throw new \moodle_exception('vgfull', 'local_visudesk');
        }
        $now = self::now();
        $seqs = [];
        foreach ($items as $item) {
            $last++;
            $DB->insert_record(self::ENTRIES, (object)['userid' => $userid, 'seq' => $last, 'device' => $device,
                'timecreated' => $now, 'data' => $item]);
            $seqs[] = $last;
        }
        $transaction->allow_commit();
        return ['stale' => false, 'seqs' => $seqs, 'last' => $last];
    }

    /**
     * Stores the snapshot (the whole grade book up to a number), replacing the previous one.
     *
     * @param int $userid
     * @param int $seq
     * @param string $data
     */
    public static function set_snapshot(int $userid, int $seq, string $data): void {
        global $DB;
        self::check_blob($data, self::MAXSNAPSHOT);
        if ($seq < 1 || $seq > self::last($userid)) {
            throw new \invalid_parameter_exception('Snapshot number unknown');
        }
        $old = self::snapshot($userid);
        if ($old && (int)$old->seq > $seq) {
            return; // A newer snapshot is already there.
        }
        $record = (object)['userid' => $userid, 'seq' => $seq, 'timecreated' => self::now(), 'data' => $data];
        if ($old) {
            $record->id = $old->id;
            $DB->update_record(self::SNAPSHOTS, $record);
        } else {
            $DB->insert_record(self::SNAPSHOTS, $record);
        }
    }

    /**
     * Deletes the changes up to a number – only those a snapshot covers (deletion period, school regulation).
     *
     * @param int $userid
     * @param int $upto
     * @return int deleted changes
     */
    public static function purge(int $userid, int $upto): int {
        global $DB;
        $snapshot = self::snapshot($userid);
        if (!$snapshot || (int)$snapshot->seq < $upto) {
            throw new \moodle_exception('vgnosnapshot', 'local_visudesk');
        }
        $count = $DB->count_records_select(self::ENTRIES, 'userid = ? AND seq <= ?', [$userid, $upto]);
        $DB->delete_records_select(self::ENTRIES, 'userid = ? AND seq <= ?', [$userid, $upto]);
        return $count;
    }

    /**
     * Nightly: deletes changes older than 30 days that the user's snapshot covers, and old pairing requests.
     *
     * @return array ['deleted' => int, 'waiting' => int users whose changes are older than 60 days without snapshot]
     */
    public static function cleanup(): array {
        global $DB;
        $before = self::now() - self::KEEPDAYS * DAYSECS * 1000;
        $sql = 'SELECT e.id FROM {' . self::ENTRIES . '} e
                  JOIN {' . self::SNAPSHOTS . '} s ON s.userid = e.userid
                 WHERE e.timecreated < :before AND e.seq <= s.seq';
        $ids = $DB->get_fieldset_sql($sql, ['before' => $before]);
        foreach (array_chunk($ids, 500) as $chunk) {
            $DB->delete_records_list(self::ENTRIES, 'id', $chunk);
        }
        $waiting = (int)$DB->count_records_sql('SELECT COUNT(DISTINCT e.userid) FROM {' . self::ENTRIES . '} e
            LEFT JOIN {' . self::SNAPSHOTS . '} s ON s.userid = e.userid AND e.seq <= s.seq
            WHERE s.id IS NULL AND e.timecreated < :old', ['old' => self::now() - 2 * self::KEEPDAYS * DAYSECS * 1000]);
        $DB->delete_records_select(self::PAIRS, 'timecreated < ?', [self::now() - self::PAIRMINUTES * 60000]);
        return ['deleted' => count($ids), 'waiting' => $waiting];
    }

    /**
     * New device asks to be paired: leaves its public key, gets an id to collect the answer.
     *
     * @param int $userid
     * @param string $publickey
     * @param string $devicename
     * @return int
     */
    public static function pair_request(int $userid, string $publickey, string $devicename): int {
        global $DB;
        self::check_blob($publickey, 256);
        $DB->delete_records_select(self::PAIRS, 'userid = ? AND timecreated < ?', [$userid,
            self::now() - self::PAIRMINUTES * 60000]);
        return $DB->insert_record(self::PAIRS, (object)['userid' => $userid, 'publickey' => $publickey,
            'devicename' => \core_text::substr(clean_param($devicename, PARAM_TEXT), 0, 50), 'answer' => '',
            'timecreated' => self::now()]);
    }

    /**
     * Open pairing requests of a user (for a device that is already set up).
     *
     * @param int $userid
     * @return array
     */
    public static function pair_open(int $userid): array {
        global $DB;
        $out = [];
        $open = $DB->get_records_select(
            self::PAIRS,
            "userid = ? AND timecreated >= ? AND answer = ''",
            [$userid, self::now() - self::PAIRMINUTES * 60000],
            'id'
        );
        foreach ($open as $r) {
            $out[] = ['id' => (int)$r->id, 'publickey' => $r->publickey, 'devicename' => $r->devicename,
                'timecreated' => (int)$r->timecreated];
        }
        return $out;
    }

    /**
     * Answer of a set-up device: the sync key, encrypted for the new device's public key.
     *
     * @param int $userid
     * @param int $id
     * @param string $answer
     */
    public static function pair_answer(int $userid, int $id, string $answer): void {
        global $DB;
        self::check_blob($answer, 4096);
        $r = $DB->get_record(self::PAIRS, ['id' => $id, 'userid' => $userid]);
        if (!$r || (int)$r->timecreated < self::now() - self::PAIRMINUTES * 60000 || $r->answer !== '') {
            throw new \moodle_exception('vgpairgone', 'local_visudesk');
        }
        $DB->set_field(self::PAIRS, 'answer', $answer, ['id' => $id]);
    }

    /**
     * The new device collects the answer; the request is deleted once collected.
     *
     * @param int $userid
     * @param int $id
     * @return string '' while there is no answer yet
     */
    public static function pair_collect(int $userid, int $id): string {
        global $DB;
        $r = $DB->get_record(self::PAIRS, ['id' => $id, 'userid' => $userid]);
        if (!$r || (int)$r->timecreated < self::now() - self::PAIRMINUTES * 60000) {
            throw new \moodle_exception('vgpairgone', 'local_visudesk');
        }
        if ($r->answer === '') {
            return '';
        }
        $DB->delete_records(self::PAIRS, ['id' => $id]);
        return $r->answer;
    }

    /**
     * Deletes everything of a user (end sync, account deleted).
     *
     * @param int $userid
     */
    public static function delete_user(int $userid): void {
        global $DB;
        foreach ([self::ENTRIES, self::SNAPSHOTS, self::PAIRS] as $table) {
            $DB->delete_records($table, ['userid' => $userid]);
        }
    }
}
