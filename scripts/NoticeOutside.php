<?php

/**
 * @author              Dmitriy Dergachev (ArtMares)
 * @date                20.03.2017
 * @copyright           artmares@influ.su
 */
class NoticeOutside extends Notice {
    
    public function __construct($parent = null, $title, $message, $type) {
        parent::__construct($parent, $title, $message, $type);
    }
}