<?php

/**
 * @author              Dmitriy Dergachev (ArtMares)
 * @date                23.03.2017
 * @copyright           artmares@influ.su
 */
class Json {
    
    static public function read($str) {
        $json = QJsonDocument::fromJson($str);
        if(!$json->isNull()) {
            return self::parseToType($json);
        }
        return null;
    }
    
    static private function parseToType($object) {
        $class = get_class($object);
        switch($class) {
            case 'QJsonDocument':
                return self::parseDocument($object);
                break;
            case 'QJsonObject':
                return self::parseObject($object);
                break;
            case 'QJsonArray':
                return self::parseArray($object);
                break;
            case 'QJsonValue':
                return self::parseValue($object);
                break;
            default:
                return null;
        }
    }
    
    static private function parseDocument($object) {
        if($object->isObject()) return self::parseToType($object->object());
        if($object->isArray()) return self::parseToType($object->array());
        return null;
    }
    
    static private function parseObject($object) {
        if(!$object->isEmpty()) {
            $data = [];
            foreach($object->keys() as $key) {
                $data[$key] = self::parseToType($object->value($key));
            }
            return $data;
        }
        return null;
    }
    
    static private function parseArray($object) {
        if(!$object->isEmpty()) {
            $n = $object->count();
            $data = [];
            for($i = 0; $i < $n; $i++) {
                $data[$i] = self::parseToType($object->at($i));
            }
            return $data;
        }
        return null;
    }
    
    static private function parseValue($object) {
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
                return self::parseArray($object->toArray());
                break;
            case QJsonValue::Obejct:
                return self::parseObject($object->toObject());
                break;
            default:
                return null;
        }
    }
}