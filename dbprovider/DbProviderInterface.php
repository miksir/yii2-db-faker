<?php

namespace MiksIr\Yii2DbFaker\dbprovider;

interface DbProviderInterface
{
    /**
     * @param integer $count
     * @return void
     */
    public function export($count);
}