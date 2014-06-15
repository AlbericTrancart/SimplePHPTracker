<?php
require_once 'TrackerConfig.class.php';
require_once 'TrackerBuilder.class.php';
require_once 'TrackerBDD.class.php';

//Load the configuration and announce the torrent
class TrackerCore{
    //Config
    protected $_tracker;
    protected $_ip;
    protected $_interval;
    protected $_load_balancing;

    //Config loading
    public function  __construct(TrackerConfig $config){
        list($host, $db, $user, $pass) = $config->getMulti(array(
            'sql_host',
            'sql_db',
            'sql_user',
            'sql_password'
        ), true);
                
        $this->_tracker = new TrackerBDD($host, $db, $user, $pass);   //Speaks with the database
        $this->_ip = $_SERVER['REMOTE_ADDR'];                         //The client's IP address
        $this->_interval = $config->get('interval', false, 60);
        $this->_load_balancing = $config->get('load_balancing', false, true);
    }

    public function announce(TrackerConfig $get){
        try{
            try{
                list($info_hash, $peer_id, $port, $uploaded, $downloaded, $left) = $get->getMulti(array(
                    'info_hash',
                    'peer_id',
                    'port',
                    'uploaded',
                    'downloaded',
                    'left',
                ), true);
            }
            catch(Exception $e){
                return $this->announceFailure('Tracker: invalid parameters; '.$e->getMessage());
            }
            
            //The IP address can be given in the GET
            $ip = $get->get('ip', false, $this->_ip);
            $event = $get->get('event', false, '');
            
            //Check the parameters
            if(strlen($info_hash) != 20){
                return $this->announceFailure('Tracker: invalid info_hash length');
            }
            if(strlen($peer_id) != 20){
                return $this->announceFailure('Tracker: invalid peer_id length');
            }
            if(!(is_numeric($port) && is_int($port = $port+0) && $port >= 0)){
                return $this->announceFailure('Tracker: invalid port');
            }
            if(!(is_numeric($uploaded) && is_int($uploaded = $uploaded+0) && $uploaded >= 0)){
                return $this->announceFailure('Tracker: invalid uploaded value');
            }
            if(!(is_numeric($downloaded) && is_int($downloaded = $downloaded+0) && $downloaded >= 0)){
                return $this->announceFailure('Tracker: invalid downloaded value');
            }
            if(!(is_numeric($left) && is_int($left = $left+0) && $left >= 0)){
                return $this->announceFailure('Tracker: invalid left value');
            }

            $interval = intval($this->_interval);
            
            $this->_tracker->saveAnnounce(
                $info_hash,
                $peer_id,
                $ip,
                $port,
                $downloaded,
                $uploaded,
                $left,
                ($event == 'completed' || $left == 0)?'complete':'incomplete',  //Consider a torrent as complete if the client says so
                ($event == 'stopped')?0:$interval*10                            //If the client gracefully stops the torrent, set its Time To Live to 0
            );
            
            $peers = $this->_tracker->getPeers($info_hash, $peer_id, $get->get('compact', false, false), $get->get('no_peer_id', false, false));
            $peer_stats = $this->_tracker->getPeerStats($info_hash, $peer_id);

            if($this->_load_balancing === true){
                //Add a 10% dispersion
                $interval = $interval + mt_rand(round($interval/-10), round($interval/10));
            }

            $announce_response = array(
                'interval'      => $interval,
                'complete'      => intval($peer_stats['complete']),
                'incomplete'    => intval($peer_stats['incomplete']),
                'peers'         => $peers,
            );

            return BencodeBuilder::build($announce_response);
        }
        catch(Exception $e){
            trigger_error('Tracker: error while announcing; '.$e->getMessage(), E_USER_WARNING);
            return $this->announceFailure('Tracker: internal error; '.$e->getMessage());
        }
    }

    //Return a Bencoded error message
    protected function announceFailure($message){
        return BencodeBuilder::build(array(
            'failure reason' => $message
        ));
    }
}
