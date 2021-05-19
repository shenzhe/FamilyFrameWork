<?php
namespace Family\Pool;

interface PoolInterface
{
    /**
     * @return mixed
     * @desc 获取
     */
    public function get();

    /**
     * @param $data
     * @return mixed
     * @desc 存入pool
     */
    public function put($data);

    /**
     * @return int
     */
    public function getLength();

    /**
     * @return mixed
     * @desc 释放
     */
    public function release();
}