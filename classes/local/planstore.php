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

namespace local_visudesk\local;

/**
 * A teacher's own lesson planning (topic, plan, how far the class got, ...), one record per lesson.
 *
 * Each field carries the server time of its last change in milliseconds. An app sends a change together with the time
 * it knew for that field (0 = the field was empty). If somebody else changed exactly that field in between, the
 * change is refused for that field only and the current value goes back to the app. Changes to other fields of the
 * same lesson, say the plan on the Mac and "how far we got" on the iPad, are merged without a question.
 *
 * The plugin does not interpret the values; the apps decide which fields exist. Each user only ever reads and writes
 * their own records.
 *
 * @package    local_visudesk
 * @copyright  2026 Holger Sebastião
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class planstore {
    /** @var string Table. */
    const TABLE = 'local_visudesk_plan';

    /** @var string Lesson key: date, then anything the app uses (start time, classes, subject), at most 100 characters. */
    const KEY = '/^\d{4}-\d{2}-\d{2}\|[^\x00-\x1f]{1,89}$/u';

    /** @var string Field names. */
    const FIELD = '/^[a-z][a-z0-9_]{0,29}$/';

    /** @var int Largest stored lesson in bytes (JSON). */
    const MAXBYTES = 65536;

    /** @var int Most lessons in one import. */
    const MAXIMPORT = 5000;

    /** @var int Days to keep a lesson after its last change, unless the setting says otherwise. */
    const RETENTIONDAYS = 730;

    /**
     * Current server time in milliseconds.
     *
     * @return int
     */
    public static function now(): int {
        return (int)floor(microtime(true) * 1000);
    }

    /**
     * Checks a lesson key.
     *
     * @param string $key
     * @return string
     */
    public static function check_key(string $key): string {
        if (\core_text::strlen($key) > 100 || !preg_match(self::KEY, $key)) {
            throw new \invalid_parameter_exception('Invalid lesson key');
        }
        return $key;
    }

    /**
     * Decodes a JSON object sent by an app.
     *
     * @param string $json
     * @return array
     */
    public static function decode(string $json): array {
        $data = json_decode($json, true);
        if (!is_array($data) || ($data && array_is_list($data))) {
            throw new \invalid_parameter_exception('Expected a JSON object');
        }
        return $data;
    }

    /**
     * Lessons of a user in a date range (both inclusive, YYYY-MM-DD), optionally only those changed after $since.
     *
     * @param int $userid
     * @param string $from
     * @param string $to
     * @param int $since Milliseconds, 0 = all
     * @return array Records with lessonkey, fields (array), timemodified
     */
    public static function get(int $userid, string $from, string $to, int $since = 0): array {
        global $DB;
        foreach ([$from, $to] as $date) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                throw new \invalid_parameter_exception('Invalid date');
            }
        }
        // Compare with the bare dates only (from, and the day after to): digits sort the same in every collation.
        $next = (new \DateTimeImmutable($to, new \DateTimeZone('UTC')))->modify('+1 day')->format('Y-m-d');
        $sql = 'userid = :userid AND lessonkey >= :firstday AND lessonkey < :nextday AND timemodified > :since';
        $records = $DB->get_records_select(
            self::TABLE,
            $sql,
            ['userid' => $userid, 'firstday' => $from, 'nextday' => $next, 'since' => $since],
            'lessonkey'
        );
        $out = [];
        foreach ($records as $record) {
            $out[] = ['lessonkey' => $record->lessonkey, 'fields' => self::fields($record),
                'timemodified' => (int)$record->timemodified];
        }
        return $out;
    }

    /**
     * Stored fields of a record.
     *
     * @param \stdClass|false|null $record
     * @return array field => ['v' => value or null, 't' => milliseconds]
     */
    public static function fields($record): array {
        if (!$record) {
            return [];
        }
        $fields = json_decode($record->fields, true);
        return is_array($fields) ? $fields : [];
    }

    /**
     * Saves changes to one lesson.
     *
     * @param int $userid
     * @param string $key Lesson key
     * @param array $changes field => ['v' => new value, null = remove, 'basis' => time the app knew, 0 = was empty]
     * @return array ['fields' => all fields now, 'conflicts' => names of refused fields, 'timemodified' => ms]
     */
    public static function save(int $userid, string $key, array $changes): array {
        global $DB;
        self::check_key($key);
        foreach ($changes as $name => $change) {
            if (
                !is_string($name) || !preg_match(self::FIELD, $name) || !is_array($change)
                    || !array_key_exists('v', $change)
            ) {
                throw new \invalid_parameter_exception('Invalid change');
            }
        }
        return self::locked($userid, function () use ($DB, $userid, $key, $changes) {
            $record = $DB->get_record(self::TABLE, ['userid' => $userid, 'lessonkey' => $key]);
            $fields = self::fields($record);
            $now = self::now();
            $conflicts = [];
            $changed = false;
            foreach ($changes as $name => $change) {
                $known = (int)($fields[$name]['t'] ?? 0);
                if ((int)($change['basis'] ?? 0) !== $known) {
                    $conflicts[] = $name;
                    continue;
                }
                $value = $change['v'];
                if ($value === '' || $value === []) {
                    $value = null;
                }
                if ($value === null && !isset($fields[$name])) {
                    continue;
                }
                $fields[$name] = ['v' => $value, 't' => max($now, $known + 1)];
                $changed = true;
            }
            $timemodified = $record ? (int)$record->timemodified : 0;
            if ($changed) {
                $timemodified = max($now, $timemodified + 1);
                self::store($record, $userid, $key, $fields, $timemodified);
            }
            return ['fields' => $fields, 'conflicts' => $conflicts, 'timemodified' => $timemodified];
        });
    }

    /**
     * First upload from an app that kept the planning locally. Takes each field only where the server has none yet,
     * so a second import or an import from another computer never overwrites anything.
     *
     * @param int $userid
     * @param array $lessons key => [field => value]
     * @return array ['added' => fields taken, 'skipped' => fields already there]
     */
    public static function import(int $userid, array $lessons): array {
        global $DB;
        if (count($lessons) > self::MAXIMPORT) {
            throw new \invalid_parameter_exception('Too many lessons');
        }
        foreach ($lessons as $key => $values) {
            self::check_key((string)$key);
            if (!is_array($values)) {
                throw new \invalid_parameter_exception('Invalid lesson');
            }
            foreach (array_keys($values) as $name) {
                if (!is_string($name) || !preg_match(self::FIELD, $name)) {
                    throw new \invalid_parameter_exception('Invalid field');
                }
            }
        }
        return self::locked($userid, function () use ($DB, $userid, $lessons) {
            $added = $skipped = 0;
            $now = self::now();
            $transaction = $DB->start_delegated_transaction();
            foreach ($lessons as $key => $values) {
                $record = $DB->get_record(self::TABLE, ['userid' => $userid, 'lessonkey' => (string)$key]);
                $fields = self::fields($record);
                $changed = false;
                foreach ($values as $name => $value) {
                    if ($value === null || $value === '' || $value === []) {
                        continue;
                    }
                    if (isset($fields[$name])) {
                        $skipped++;
                        continue;
                    }
                    $fields[$name] = ['v' => $value, 't' => $now];
                    $changed = true;
                    $added++;
                }
                if ($changed) {
                    self::store($record, $userid, (string)$key, $fields, max($now, $record ? $record->timemodified + 1 : 0));
                }
            }
            $transaction->allow_commit();
            return ['added' => $added, 'skipped' => $skipped];
        });
    }

    /**
     * Writes a record.
     *
     * @param \stdClass|false|null $record Existing record or none
     * @param int $userid
     * @param string $key
     * @param array $fields
     * @param int $timemodified
     */
    protected static function store($record, int $userid, string $key, array $fields, int $timemodified): void {
        global $DB;
        ksort($fields);
        $json = json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false || strlen($json) > self::MAXBYTES) {
            throw new \invalid_parameter_exception('Lesson too large');
        }
        if ($record) {
            $DB->update_record(self::TABLE, (object)['id' => $record->id, 'fields' => $json, 'timemodified' => $timemodified]);
        } else {
            $DB->insert_record(self::TABLE, (object)['userid' => $userid, 'lessonkey' => $key, 'fields' => $json,
                'timemodified' => $timemodified]);
        }
    }

    /**
     * Runs $work while holding a lock for the user, so that two devices saving at once cannot lose a change.
     *
     * @param int $userid
     * @param callable $work
     * @return mixed
     */
    protected static function locked(int $userid, callable $work) {
        $factory = \core\lock\lock_config::get_lock_factory('local_visudesk_plan');
        $lock = $factory->get_lock('user' . $userid, 10);
        if (!$lock) {
            throw new \moodle_exception('locktimeout');
        }
        try {
            return $work();
        } finally {
            $lock->release();
        }
    }

    /**
     * Days to keep a lesson after its last change (0 = keep).
     *
     * @return int
     */
    public static function retention_days(): int {
        $days = get_config('local_visudesk', 'planretentiondays');
        return $days === false ? self::RETENTIONDAYS : max(0, (int)$days);
    }

    /**
     * Removes lessons not changed for longer than the retention time.
     *
     * @return int Removed lessons
     */
    public static function cleanup(): int {
        global $DB;
        $days = self::retention_days();
        if (!$days) {
            return 0;
        }
        $before = self::now() - $days * DAYSECS * 1000;
        $count = $DB->count_records_select(self::TABLE, 'timemodified < ?', [$before]);
        $DB->delete_records_select(self::TABLE, 'timemodified < ?', [$before]);
        return $count;
    }

    /**
     * Removes all lessons of a user.
     *
     * @param int $userid
     */
    public static function delete_user(int $userid): void {
        global $DB;
        $DB->delete_records(self::TABLE, ['userid' => $userid]);
    }
}
