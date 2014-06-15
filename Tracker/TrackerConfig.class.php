<?php
//Store any form of data in an array with custom get methods.
class TrackerConfig {
    protected $config_values;

    //Initialization
    public function __construct(array $config_values){
        $this->config_values = $config_values;
    }

    //Retrieve a value from the current configuration
    //If a value does not exist, throws an error if $needed, else return $default
    public function get($config_name, $needed = true, $default = null){
        if (!isset($this->config_values[$config_name])){
            if($needed){
                throw new Exception('Tracker: value "'.$config_name.'" not found');
            }
            return $default;
        }
        return $this->config_values[$config_name];
    }

    //Retrieve multiple values
    public function getMulti(array $config_names, $needed = true, $defaults = null){
        $return = array();
        foreach($config_names as $index => $config_name){
            //The $default parameter can be an array with default value of each parameter or a single general value
            $return[] = $this->get($config_name, $needed, (is_array($defaults)?$defaults[$index]:$defaults));
        }
        return $return;
    }
}
