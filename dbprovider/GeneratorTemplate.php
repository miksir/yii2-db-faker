<?php


namespace MiksIr\Yii2DbFaker\dbprovider;

use MiksIr\Yii2DbFaker\Exception;
use MiksIr\Yii2DbFaker\generator\GeneratorInterface;

class GeneratorTemplate implements DbProviderInterface
{
    /** @var GeneratorInterface  */
    private $generator;


    public function __construct(GeneratorInterface $generator)
    {
        $this->generator = $generator;
    }

    /**
     * @param integer $count
     * @throws Exception
     */
    public function export($count)
    {
        throw new Exception("GeneratorTemplate: you can use this class as template for new dbprovider classes.");

        foreach ($this->generator->generate() as $item) {
            // do
            if (--$count <= 0) {
                break;
            }
        }
    }
}