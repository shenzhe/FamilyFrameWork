<?php
//file framework/Family/MVC/Entity.php
namespace Family\MVC;

use ValidateInput\ValidateInput;
use Family\Core\Log;

abstract class Entity
{
    /**
     * Entity constructor.
     * @param array $array
     * @desc 把数组填充到entity
     */
    public function __construct(array $array)
    {
        if (empty($array)) {
            return;
        }

        foreach ($array as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        $validate = $this->getValidate();
        Log::debug("validate:" . json_encode($validate));
        Log::debug("data:" . json_encode($array));
        if (!empty($validate)) {
            foreach ($validate as $key => $value) {
                $this->$key = ValidateInput::vaild($this->$key, $value[0], $value[1]);
            }
        }
    }

    /**
     * @return array
     * @desc 入库参数验证, 格式 [
     *     "key" => [验证格式, 错误提示]
     * ]
     */
    public function getValidate(): array
    {
        return [];
    }
}