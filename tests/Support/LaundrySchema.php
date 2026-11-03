<?php

namespace Tests\Support;

use RuntimeException;

final class LaundrySchema
{
    /** @return list<string> */
    public static function statements(string $sql): array
    {
        $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);
        $statements = [];
        $buffer = '';
        $quote = null;
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $character = $sql[$i];
            $next = $sql[$i + 1] ?? '';

            if ($quote !== null) {
                $buffer .= $character;

                if ($character === '\\' && $next !== '') {
                    $buffer .= $next;
                    $i++;
                } elseif ($character === $quote) {
                    if ($next === $quote) {
                        $buffer .= $next;
                        $i++;
                    } else {
                        $quote = null;
                    }
                }

                continue;
            }

            if (in_array($character, ["'", '"', '`'], true)) {
                $quote = $character;
                $buffer .= $character;
            } elseif ($character === '#' || ($character === '-' && $next === '-' && ctype_space($sql[$i + 2] ?? ' '))) {
                $end = strpos($sql, "\n", $i);
                $i = $end === false ? $length : $end;
                $buffer .= ' ';
            } elseif ($character === '/' && $next === '*') {
                $end = strpos($sql, '*/', $i + 2);

                if ($end === false) {
                    throw new RuntimeException('Unterminated SQL comment in laundry fixture.');
                }

                $i = $end + 1;
                $buffer .= ' ';
            } elseif ($character === ';') {
                if (trim($buffer) !== '') {
                    $statements[] = trim($buffer);
                }

                $buffer = '';
            } else {
                $buffer .= $character;
            }
        }

        if ($quote !== null) {
            throw new RuntimeException('Unterminated SQL quote in laundry fixture.');
        }

        if (trim($buffer) !== '') {
            $statements[] = trim($buffer);
        }

        $safe = [];

        foreach ($statements as $statement) {
            // The fixture names the main database. These statements must never reach PDO.
            if (preg_match('/\A(?:CREATE\s+DATABASE\b|USE\b)/i', $statement)) {
                continue;
            }

            if (! preg_match('/\A(?:CREATE\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?|INSERT\s+INTO)\s+`?(?:jenis_layanan|pesanan_laundry)`?\s*(?:\(|VALUES\b)/i', $statement)) {
                throw new RuntimeException('Only CREATE TABLE and INSERT for the two laundry tables are allowed.');
            }

            if (preg_match_all('/\bREFERENCES\s+([^\s(]+)/i', $statement, $references)) {
                foreach ($references[1] as $table) {
                    if (! in_array(str_replace('`', '', $table), ['jenis_layanan', 'pesanan_laundry'], true)) {
                        throw new RuntimeException('Foreign keys must reference an unqualified laundry table.');
                    }
                }
            }

            $safe[] = $statement;
        }

        if ($safe === []) {
            throw new RuntimeException('The laundry SQL fixture contains no table or seed statements.');
        }

        return $safe;
    }
}
