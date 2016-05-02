<?php

namespace MiksIr\Yii2DbFaker\generator;

use MiksIr\Yii2DbFaker\Exception;

class FakerGenerator implements GeneratorInterface
{
    private $generator;
    private $iterate = 0;
    private $templateFullPath;

    /**
     * @var string locale of faker
     */
    public $language;

    /**
     * @var string directory with templates
     */
    public $directory = '@tests/unit/templates/fixtures/';

    /**
     * @var string (required) name of template (name of php file, see yiisoft/yii2-faker description)
     */
    public $template;

    public function generate($count=null)
    {
        if (!$this->templateFullPath) {
            $this->templateFullPath = $this->resolvePath($this->directory, $this->template);
        }

        if (is_null($this->generator)) {
            $this->generator = $this->templateFullPath ? \Faker\Factory::create($this->templateFullPath) : \Faker\Factory::create($this->templateFullPath);
        }

        $faker = $this->generator;

        while (is_null($count) || $count > 0) {
            $index = $this->iterate++;
            $count--;
            $data = require($this->templateFullPath);
            yield $data;
        }
    }

    /**
     * Resolve Yii aliases and check template file
     * @param $path_alias
     * @return string
     * @throws Exception
     */
    private function resolvePath($path_alias, $file_name)
    {
        $path = \Yii::getAlias($path_alias, false);
        $path = $path ? realpath($path) : $path;
        $file_name = !preg_match('/\.php$/i', $file_name) ? $file_name . '.php' : $file_name;

        if (!$path || !is_dir($path) || !file_exists($path . '/' . $file_name)) {
            throw new Exception("Faker template \"{$path}/{$file_name}\" not found");
        }

        return $path . '/' . $file_name;
    }

}