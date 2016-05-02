<?php


namespace MiksIr\Yii2DbFaker\dbprovider;

use MiksIr\Yii2DbFaker\Exception;
use MiksIr\Yii2DbFaker\generator\GeneratorInterface;
use yii\db\Connection;

class YiiDAO implements DbProviderInterface
{
    const TRUNCATE_TRUNCATE = 1;
    const TRUNCATE_DELETE = 2;
    const TRUNCATE_TRUNCATE_CASCADE = 3;

    /** @var GeneratorInterface  */
    private $generator;
    /** @var  Connection */
    private $db;

    /**
     * @var string (required) table name
     */
    public $table;
    private $table_quoted;

    /**
     * @var string Yii name of db component
     */
    public $db_component = 'db';

    /**
     * @var int truncate before insert, 1 - truncate, 2 - delete all, 3 - truncate cascade
     */
    public $truncate = 0;

    /**
     * @var int use multirow inserts. Attn: total number of rows can exceed count on floor(placeholder_limit / fields in row)
     */
    public $multirow = 1;

    /**
     * @var int maximum number of placehoders used in multirow insert. number of row per insert = floor(placeholder_limit / fields in row)
     */
    public $placeholder_limit = 2100;

    public function __construct(GeneratorInterface $generator)
    {
        $this->generator = $generator;
    }

    /**
     * @param integer $count
     * @return \Generator
     * @throws Exception
     */
    public function export($count)
    {
        if (!$this->table) {
            throw new Exception("table name is required");
        }

        if (is_null($this->db)) {
            $this->db = \Yii::$app->get($this->db_component);
        }

        $this->table_quoted = $this->db->quoteTableName($this->table);

        $this->truncate();

        $first_row = $this->generator->generate()->current();

        // prepare quoted fileds name list
        $fields = array_map(function($i) {
            return $this->db->quoteColumnName($i);
        }, array_keys($first_row));
        $fields_str = implode(",", $fields);

        // prepare values section, repeat values block $rows_per_request number
        $rows_per_request = 1;
        if ($this->multirow) {
            $rows_per_request = (int)max(floor($this->placeholder_limit / count($fields)), 1);
        }
        $placeholders = "(". implode(",", array_fill(1, count($fields), '?')) . ")";
        $value_placeholders = implode(",", array_fill(1, $rows_per_request, $placeholders));
        
        // finally sql request
        $prepare = $this->db->createCommand("INSERT INTO {$this->table_quoted}({$fields_str}) VALUES {$value_placeholders}");

        // first row of data, lets save it
        $insert_values = $this->array1based($first_row);
        $prepared_rows = 1;

        foreach ($this->generator->generate() as $item) {

            if ($prepared_rows === $rows_per_request) {
                unset($insert_values[0]);
                $prepare->bindValues($insert_values);
                $prepare->execute();

                $count = $count - $rows_per_request;
                if ($count <= 0) {
                    break;
                }
                yield $count;

                $insert_values = $this->array1based($item);
                $prepared_rows = 1;

            } else {
                $insert_values = array_merge($insert_values, array_values($item));
                $prepared_rows ++;
            }

        }
    }

    /**
     * @throws Exception
     */
    private function truncate()
    {
        if ($this->truncate) {

            switch ($this->truncate) {
                case self::TRUNCATE_TRUNCATE:
                    $sql = "TRUNCATE {$this->table_quoted}";
                    break;
                case self::TRUNCATE_DELETE:
                    $sql = "DELETE FROM {$this->table_quoted}";
                    break;
                case self::TRUNCATE_TRUNCATE_CASCADE:
                    $sql = "TRUNCATE {$this->table_quoted} CASCADE";
                    break;
                default:
                    throw new Exception("Unknown truncate mode {$this->truncate}");
            }

            $this->db->createCommand($sql)->execute();
        }
    }

    private function array1based($array)
    {
        $new = array_values($array);
        array_unshift($new, null);
        return $new;
    }
}