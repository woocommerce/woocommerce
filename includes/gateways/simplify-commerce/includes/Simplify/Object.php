<?php
/*
 * Copyright (c) 2013 - 2015 MasterCard International Incorporated
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without modification, are 
 * permitted provided that the following conditions are met:
 * 
 * Redistributions of source code must retain the above copyright notice, this list of 
 * conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright notice, this list of 
 * conditions and the following disclaimer in the documentation and/or other materials 
 * provided with the distribution.
 * Neither the name of the MasterCard International Incorporated nor the names of its 
 * contributors may be used to endorse or promote products derived from this software 
 * without specific prior written permission.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY 
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES 
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT 
 * SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
 * TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; 
 * OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER 
 * IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING 
 * IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF 
 * SUCH DAMAGE.
 */


/**
 * Base class for all domain objects.
 */
class Simplify_Object {

    private $properties = array();

    /**
     * @ignore
     */
    public function __get($key) {
        if (array_key_exists($key, $this->properties)) {
            return $this->properties[$key];
        } else {
            return null;
        }
    }

    /**
     * @ignore
     */
    public function __set($key, $value) {
        $this->properties[$key] = $value;
    }

    /**
     * Updates the object's properties with the values in the specified map.
     * @param $hash array Map of values to set.
     */
    public function setAll($hash) {
        foreach ($hash as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * @ignore
     */
    public function __toString() {
        return json_encode($this->properties);
    }

    /**
     * Returns the object's properties as a map.
     * @return array map of properties.
     */
    public function getProperties() {
        return $this->properties;
    }
}