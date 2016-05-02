<?php


namespace MiksIr\Yii2DbFaker\dbprovider;

use MiksIr\Yii2DbFaker\Exception;
use MiksIr\Yii2DbFaker\generator\GeneratorInterface;

class Csv implements DbProviderInterface
{
    /** @var GeneratorInterface  */
    private $generator;

    /**
     * @var string name of output file, stdout if empty
     */
    public $filename;
    private $fh;
    
    public function __construct(GeneratorInterface $generator)
    {
        $this->generator = $generator;
    }

    /**
     * @param integer $count
     * @return \Generator
     */
    public function export($count)
    {
        $first = $this->generator->generate()->current();
        $this->out(array_keys($first));
        $this->out(array_values($first));
        foreach ($this->generator->generate() as $data) {
            if (--$count <= 0) {
                break;
            }
            $this->out($data);
            yield $count;
        }
    }

    /**
     * @param array $data
     * @throws Exception
     */
    private function out($data)
    {
        if ($this->filename) {
            if (file_exists($this->filename)) {
                throw new Exception("{$this->filename} already exists, can't override");
            }
            $this->fh = fopen($this->filename, 'x');
        } else {
            $this->fh = fopen("php://stdout", 'w');
        }
        if ($this->fh) {
            fputcsv($this->fh, $data);
        }
    }
}