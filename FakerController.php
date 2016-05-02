<?php


namespace MiksIr\Yii2DbFaker;


use MiksIr\Yii2DbFaker\dbprovider\DbProviderInterface;
use MiksIr\Yii2DbFaker\generator\GeneratorInterface;
use yii\console\Controller;
use yii\di\Container;
use yii\helpers\Console;

/**
 * This command can fill your database with fake data.
 */
class FakerController extends Controller
{
    /**
     * @var int number of data rows
     */
    public $count = 1000;

    /**
     * @var int do not ask confirmation
     */
    public $force = 0;

    /**
     * @var string class name of data generator
     */
    public $generator = 'FakerGenerator';
    private $generator_fqn;
    /** @var GeneratorInterface */
    private $generator_obj;

    /**
     * @var string class of database store provider
     */
    public $dbprovider = 'Csv';
    private $dbprovider_fqn;
    /** @var  DbProviderInterface */
    private $dbprovider_obj;


    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        return array_merge(parent::options($actionID), [
            'count', 'force', 'generator', 'dbprovider'
        ]);
    }

    public function beforeAction($action)
    {
        $this->generator_fqn = $this->fixNamespace($this->generator, 'MiksIr\\Yii2DbFaker\\generator');
        $this->dbprovider_fqn = $this->fixNamespace($this->dbprovider, 'MiksIr\\Yii2DbFaker\\dbprovider');

        return parent::beforeAction($action);
    }


    public function actionHelp()
    {
        $this->stdout("Usage: faker/generate option=value option=value ...\n", Console::FG_YELLOW);
        if ($gen_help = $this->help_of_class($this->generator_fqn, 'generator')) {
            $this->stdout("Options for current generator \"{$this->generator}\":\n", Console::FG_GREEN);
            $this->stdout($gen_help);
        }
        if ($db_help = $this->help_of_class($this->dbprovider_fqn, 'dbprovider')) {
            $this->stdout("Options for current dbprovider \"{$this->dbprovider}\":\n", Console::FG_GREEN);
            $this->stdout($db_help);
        }
        $this->stdout("Also, you can use global options:\n",  Console::FG_GREEN);
        $this->stdout("\t--generator - class name of generator\n".
            "\t--dbprovider - class name of database storage\n".
            "\t--count=10000 - number of rows to generate\n");
    }

    /**
     * Generate fake data and
     */
    public function actionGenerate()
    {
        $input = $this->parseArguments(func_get_args());

        $container = new Container();
        $container->set(GeneratorInterface::class, array_merge(['class' => $this->generator_fqn], $input['generator']));
        $container->set(DbProviderInterface::class, array_merge(['class' => $this->dbprovider_fqn], $input['dbprovider']));

        $this->generator_obj = $container->get(GeneratorInterface::class);

        if (!$this->force && !$this->confirmGeneration()) {
            return;
        }

        $this->dbprovider_obj = $container->get(DbProviderInterface::class);
        $this->dbprovider_obj->export($this->count);
    }

    private function parseArguments($lines)
    {
        $args = [
            'generator' => [],
            'dbprovider' => [],
        ];
        foreach ($lines as $line) {
            list($key, $value) = explode('=', $line);
            if (strstr($key, '_')) {
                list ($prefix, $key) = explode('_', $key);
                $args[$prefix][$key] = $value;
            } else {
                $args[$key] = $value;
            }
        }
        return $args;
    }

    private function fixNamespace($class, $default_namespace)
    {
        $class = str_replace('/', '\\', $class);
        if (!strstr($class, '\\')) {
            $class = $default_namespace . '\\' . $class;
        }

        return $class;
    }

    private function help_of_class($class_name, $prefix)
    {
        $help = '';
        $class = new \ReflectionClass($class_name);
        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();
            $defaultValue = $property->getValue($this);
            $tags = $this->parseDocCommentTags($property);
            if (isset($tags['var']) || isset($tags['property'])) {
                $doc = isset($tags['var']) ? $tags['var'] : $tags['property'];
                if (is_array($doc)) {
                    $doc = reset($doc);
                }
                if (preg_match('/^(\S+)\s*(.*)/s', $doc, $matches)) {
                    $type = $matches[1];
                    $comment = $matches[2];
                } else {
                    $type = null;
                    $comment = $doc;
                }
            } else {
                $type = null;
                $comment = "???";
            }
            $name = $prefix . "_" . $name;
            $help .= "\t{$name} - {$comment}" . ($defaultValue ? " (defalt: {$defaultValue})" : "") . "\n";
        }
        return $help;
    }


    private function confirmGeneration()
    {
        $this->stdout("Example of fake data (1 row):\n", Console::FG_YELLOW);
        $fake_data = $this->generator_obj->generate()->current();
        foreach ($fake_data as $field=>$data) {
            $this->stdout("\t{$field}: {$data}\n");
        }
        return $this->confirm("Generate {$this->count} rows?", Console::FG_YELLOW);
    }
}