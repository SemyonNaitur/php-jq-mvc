<?php

namespace System\Libraries;

use System\Core\App;

class Validator
{
    protected ?Db $db = null;
    protected ?array $error_bag = null;
    protected array $default_opts = ['clear_error_bag' => false];

    public function __construct(Db $db = null)
    {
        $this->setDb($db ?? default_db());
    }

    /**
     * Rules array format:
     *  [
     *      'field_name' | 'field_name:field_label' => 'rule1|rule2|...' | ['rule1', 'rule2', ...]
     *  ]
     * 
     *  Rule format: 'rule_name' | 'rule_name:rule_params'
     * 
     * @param   array   $record
     * @param   array   $rules
     * @return  bool    true on success
     */
    public function validate(array $record, array $rules, $opts = []): bool
    {
        $opts = $this->checkOpts($opts);
        $errors = [];
        foreach ($rules as $fld_name => $fld_rules) {
            [$fld_name, $label] = sscanf($fld_name, '%[^:]:%s');
            $fld_label = $label ?? 'value';

            $fld_rules = (is_array($fld_rules)) ? $fld_rules : explode('|', $fld_rules);
            $val = $record[$fld_name] ?? '';
            $fld_errors = [];

            if (in_array('required', $fld_rules)) {
                if ($val === '') {
                    $fld_errors[] = ($label) ? "$label is required." : 'Required field.';
                }
            }
            if (!$fld_errors) {
                foreach ($fld_rules as $i => &$rule) {
                    $rule = preg_replace('/\s/', '', $rule);
                    [$rule_name, $params] = sscanf($rule, '%[^:]:%s');
                    if ($rule_name == 'required') continue;

                    // Make sure unique rule is checked last, and only if value is valid.
                    if ($rule_name == 'unique') {
                        if ($i < (count($fld_rules) - 1)) {
                            $fld_rules[] = $rule;
                            continue;
                        } elseif ($fld_errors) {
                            break;
                        }
                    }

                    $func = $this->toCamelCase($rule_name) . '_rule';
                    if (!method_exists($this, $func)) {
                        throw new \Exception("Invalid rule name: $rule_name");
                    }

                    if (($valid = $this->$func($val, $params ?? '', $this->db)) !== true) {
                        $fld_errors[] = ucfirst(sprintf($valid, $fld_label));
                    }
                }
            }

            if ($fld_errors) {
                $errors[$fld_name] = $fld_errors;
            }
        }
        if ($errors) {
            $this->updateErrorBag($errors);
            return false;
        } else {
            return true;
        }
    }

    public function setDb(Db $db): void
    {
        $this->db = $db;
    }

    protected function checkOpts(array $opts): array
    {
        $opts = array_merge($this->default_opts, $opts);
        if ($opts['clear_error_bag']) {
            $this->clearErrorBag();
        }
        return $opts;
    }

    protected function updateErrorBag(array $errors): array
    {
        $this->error_bag = array_merge_recursive($this->error_bag ?? [], $errors);
        return $this->error_bag;
    }

    public function getErrorBag()
    {
        return $this->error_bag;
    }

    public function clearErrorBag()
    {
        $this->error_bag = null;
    }

    //--- rules ---//

    public static function string_rule($val)
    {
        return is_string($val) ?: '%s be a string.';
    }

    public static function number_rule($val)
    {
        return is_numeric($val) ?: '%s be a number.';
    }

    public static function integer_rule($val)
    {
        return ((int) $val == $val) ?: '%s be an integer.';
    }

    public static function url_rule($val)
    {
        return (bool) filter_var($val, FILTER_VALIDATE_URL) ?: 'Invalid %s.';
    }

    public static function email_rule($val)
    {
        return (bool) filter_var($val, FILTER_VALIDATE_EMAIL) ?: 'Invalid %s address.';
    }

    public static function date_rule($val)
    {
        return (bool) preg_match('/^\d{4}(-\d{2}){2}$/', $val) ?: 'Invalid date.';
    }

    public static function datetime_rule($val)
    {
        return (bool) preg_match('/^\d{4}(-\d{2}){2} \d{2}(:\d{2}){2}$/', $val) ?: 'Invalid date or time.';
    }

    /**
     * 
     * @param   mixed       $val
     * @param   string|int  $length
     * @return  bool|string
     */
    public static function minLength_rule($val, $len)
    {
        if ((int) $len != $len || $len < 0) {
            throw new \Exception('Invalid length passed to "min_length" rule');
        }
        return (mb_strlen((string) $val) >= $len) ?: "%s must be at least $len characters long.";
    }

    /**
     * 
     * @param   mixed       $val
     * @param   string      $params format: table.column
     * @param   Db          $db
     * @return  bool|string
     */
    public static function unique_rule($val, string $params, ?Db $db)
    {
        if (!$db) throw new \Exception('An instance of Db is required');
        [$tbl, $fld] = sscanf($params, '%[^.].%s');
        if (!$tbl) throw new \Exception('Params for "unique" rule are missing table name');
        if (!$fld) throw new \Exception('Params for "unique" rule are column table name');
        return $db->isUnique($val, $fld, $tbl) ?: '%s already exists.';
    }
    //--- /rules ---//

    private static function toCamelCase($str)
    {
        $str = str_replace(['_', '-'], ' ', $str);
        $str = ucwords($str);
        $str = str_replace(' ', '', $str);
        return lcfirst($str);
    }
}
