<?php

namespace MiksIr\Yii2DbFaker\dbprovider;

interface DbProviderInterface
{
    /**
     * @param integer $count
     * @return \Generator
     */
    public function export($count);
}