<?php
//Classes doing the Bencoding stuff (getting data to a BitTorrent-readable string)

//A bencoded value. Can be an integer, a string, a dictionary or a list
abstract class BencodeAbstract {
    //Value
    protected $value;
    
    //Initialization
    abstract public function __construct($value);

    //Conversion to a Bencoded string
    abstract public function __toString();

    //Represent the value in PHP scalars or arrays
    abstract public function represent();
}

//Can be a dictionary or a list
abstract class BencodeContainer extends BencodeAbstract {
    public function __construct($value){
        $this->value = array();

        if(!isset($value)){
            return;
        }

        if(BencodeBuilder::isDictionary($value)){
            foreach($value as $key => $sub_value){
                $this->contain($sub_value, new BencodeString($key));
            }
        }
        else{
            foreach($value as $sub_value){
                $this->contain($sub_value);
            }
        }
    }

    public function represent(){
        $representation = array();
        foreach ($this->value as $key => $sub_value){
            $representation[$key] = $sub_value->represent();
        }
        return $representation;
    }

    //Add a value to the dictionary/list. The $key parameter is only used for dictionaries
    abstract public function contain(BencodeAbstract $sub_value, BencodeString $key = null );
}

//A dictionary
class BencodeDictionary extends BencodeContainer {
    public function contain(BencodeAbstract $sub_value, BencodeString $key = null){
        if(!isset($key)){
            throw new Exception('Tracker: wrong key for the dictionary value "'.$sub_value.'"');
        }
        if(isset($this->value[$key->value])){
            throw new Exception('Tracker: the dictionary key "'.$key->value.'" already exists');
        }
        $this->value[$key->value] = $sub_value;
    }

    public function __toString(){
        //All keys must be byte strings and sorted in lexicographic order
        ksort($this->value);

        $string_represent = 'd';
        foreach($this->value as $key => $sub_value){
            $key = new BencodeString($key);
            $string_represent .= $key.$sub_value;
        }
        return $string_represent.'e';
    }
}

//Integer
class BencodeInteger extends BencodeAbstract {
    public function __construct($value){
        if(!(is_numeric($value) && is_int(($value+0)))){
            throw new Exception('Tracker: "'.$value.'" is not an integer');
        }
        $this->value = intval($value);
    }

    public function __toString(){
        return 'i'.$this->value.'e';
    }

    public function represent(){
        return $this->value;
    }
}

//List indexed from 0
class BencodeList extends BencodeContainer {
    public function contain(BencodeAbstract $sub_value, BencodeString $key = null){
        $this->value[] = $sub_value;
    }

    public function __toString(){
        $string_represent = 'l';
        foreach($this->value as $sub_value){
            $string_represent .= $sub_value;
        }
        return $string_represent.'e';
    }
}

//String
class BencodeString extends BencodeAbstract {
    public function __construct($value){
        if(!is_string($value)){
            throw new Exception('Tracker: "'.$value.'" is not a string');
        }
        $this->value = $value;
    }

    public function __toString(){
        return strlen($this->value).':'.$this->value;
    }

    public function represent(){
        return $this->value;
    }
}

//Bencode a PHP value
class BencodeBuilder {
    static public function build($input){
        if(is_int($input)){
            return new BencodeInteger($input);
        }
        if(is_string($input)){
            return new BencodeString($input);
        }
        if(is_array($input)){
            //Create sub-elements in order ton build a list/dictionary
            $constructor_input = array();
            foreach($input as $key => $value){
                $constructor_input[$key] = self::build($value);
            }

            if(self::isDictionary($input)){
                return new BencodeDictionary($constructor_input);
            }
            else{
                return new BencodeList($constructor_input);
            }
        }

        throw new Exception('Tracker: invalid type "'.gettype($input).'" while building a Bencoded value');
    }

    //Tells if the input is a dictionary (key => value pairs) or an indexed list
    static public function isDictionary(array $array){
        return array_keys($array) !== range(0, (count($array)-1));
    }
}
