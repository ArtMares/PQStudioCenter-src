<?php

/**
 * @author              Dmitriy Dergachev (ArtMares)
 * @date                23.03.2017
 * @copyright           artmares@influ.su
 */
class Json {
    
    protected $data = null;
    
    public function read($str) {
        $json = QJsonDocument::fromJson($str);
        if(!$json->isNull()) {
            $this->data = $this->parseToType($json);
        }
        return $this->data;
    }
    
    private function parseToType($object) {
        $class = get_class($object);
        switch($class) {
            case 'QJsonDocument':
                return $this->parseDocument($object);
                break;
            case 'QJsonObject':
                return $this->parseObject($object);
                break;
            case 'QJsonArray':
                return $this->parseArray($object);
                break;
            case 'QJsonValue':
                return $this->parseValue($object);
                break;
            default:
                return null;
        }
    }
    
    private function parseDocument($object) {
        if($object->isObject()) return $this->parseToType($object->object());
        if($object->isArray()) return $this->parseToType($object->array());
        return null;
    }
    
    private function parseObject($object) {
        if(!$object->isEmpty()) {
            $data = [];
            foreach($object->keys() as $key) {
                $data[$key] = $this->parseToType($object->value($key));
            }
            return $data;
        }
        return null;
    }
    
    private function parseArray($object) {
        if(!$object->isEmpty()) {
            $n = $object->count();
            $data = [];
            for($i = 0; $i < $n; $i++) {
                $data[$i] = $this->parseToType($object->at($i));
            }
            return $data;
        }
        return null;
    }
    
    private function parseValue($object) {
        switch($object->type()) {
            case QJsonValue::Bool:
                return $object->toBool();
                break;
            case QJsonValue::Double:
                return $object->toDouble();
                break;
            case QJsonValue::String:
                return $object->toString();
                break;
            case QJsonValue::Array:
                return $this->parseArray($object->toArray());
                break;
            case QJsonValue::Obejct:
                return $this->parseObject($object->toObject());
                break;
            default:
                return null;
        }
    }
}