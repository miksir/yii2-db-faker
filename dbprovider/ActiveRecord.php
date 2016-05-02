<?php


namespace MiksIr\Yii2DbFaker\dbprovider;

use MiksIr\Yii2DbFaker\Exception;
use MiksIr\Yii2DbFaker\generator\GeneratorInterface;

class ActiveRecord implements DbProviderInterface
{
    /** @var GeneratorInterface  */
    private $generator;
    
    /**
     * @var string (required) model name
     */
    public $model;

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
        if (!$this->model) {
            throw new Exception("Model name is required");
        }

        foreach ($this->generator->generate() as $item)
        {
            /** @var \yii\db\ActiveRecord $model */
            $model = new $this->model;
            foreach ($item as $key => $value) {
                // we don't wanna be safe :)
                $model->$key = $value;
            }
            try {
                if (!$model->save()) {
                    throw new Exception("Model save error. Validation errors: " . var_export($model->getErrors(), true));
                }
            } catch (\Exception $e) {
                throw new Exception("Model save error. Exception: {$e->getMessage()}");
            }

            if (--$count <= 0) {
                break;
            }

            yield $count;
        }
    }
}