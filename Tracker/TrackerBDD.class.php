<?php

//Stores and retrieves data from the database
class TrackerBDD {
    protected $_bdd;

    public function __construct($host, $dbname, $user, $pass){
        $this->_bdd = new PDO('mysql:host='.$host.'; dbname='.$dbname, $user, $pass, array(PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC));
    }
    
    public function __destruct(){
        $this->_bdd = null;
    }
    
    //Prepare the parameters for the insertion into the database
    public function prepare(array $parameters){
        foreach($parameters as &$parameter){
            if(is_null($parameter)){
                $parameter = 'NULL';
            }
            else if(is_numeric($parameter)){
                //Locale unaware number representation.
                $parameter = sprintf('%.12F', $parameter);
                if(strpos($parameter, '.') !== false){
                    $parameter = rtrim(rtrim($parameter, '0'), '.');
                }
            }
        }
        return $parameters;
    }
    
    //Save a client announce
    public function saveAnnounce($info_hash, $peer_id, $ip, $port, $downloaded, $uploaded, $left, $status, $ttl){
        list($info_hash, $peer_id, $ip, $port, $downloaded, $uploaded, $left, $status, $ttl) = $this->prepare(array($info_hash, $peer_id, $ip, $port, $downloaded, $uploaded, $left, $status, $ttl));
        
        //Check if the client already announced for this torrent
        $requete = $this->_bdd->prepare('SELECT id FROM tracker_peers WHERE info_hash = :info_hash AND peer_id = :peer_id');
        $requete->bindValue(':info_hash', $info_hash);
        $requete->bindValue(':peer_id', $peer_id);
        $requete->execute();
        $resultat = $requete->rowCount();
        $requete->closeCursor();
        
        //If not we insert
        if($resultat <= 0){
            $requete = $this->_bdd->prepare('INSERT INTO tracker_peers(info_hash, peer_id, ip_address, port, bytes_downloaded, bytes_uploaded, bytes_left, status,
                                                                       expires) 
                                                    VALUES(:info_hash, :peer_id, INET_ATON(:ip), :port, :downloaded, :uploaded, :left, :status, 
                                                                       DATE_ADD(NOW(), INTERVAL :interval SECOND))');
            $requete->bindValue(':info_hash', $info_hash);
            $requete->bindValue(':peer_id', $peer_id);
            $requete->bindValue(':ip', $ip);
            $requete->bindValue(':port', $port);
            $requete->bindValue(':downloaded', $uploaded);
            $requete->bindValue(':uploaded', $downloaded);
            $requete->bindValue(':left', $left);
            $requete->bindValue(':status', $status);
            $requete->bindValue(':interval', $ttl);
            $requete->execute();
            $requete->closeCursor();
        }
        //Else we update current informations
        else if($resultat == 1){
            $requete = $this->_bdd->prepare('UPDATE tracker_peers SET ip_address = INET_ATON(:ip), port = :port, bytes_downloaded = :downloaded, 
                                             bytes_uploaded = :uploaded, bytes_left = :left, status = :status, 
                                             expires = DATE_ADD(NOW(), INTERVAL :interval SECOND) 
                                             WHERE info_hash = :info_hash AND peer_id = :peer_id');
            $requete->bindValue(':info_hash', $info_hash);
            $requete->bindValue(':peer_id', $peer_id);
            $requete->bindValue(':ip', $ip);
            $requete->bindValue(':port', $port);
            $requete->bindValue(':downloaded', $uploaded);
            $requete->bindValue(':uploaded', $downloaded);
            $requete->bindValue(':left', $left);
            $requete->bindValue(':status', $status);
            $requete->bindValue(':interval', $ttl);
            $requete->execute();
            $requete->closeCursor();
        }
        return true;
    }

    //Return all the active peers of a torrent
    //If $compact, return:
    //Nx6 bytes, where the 4 beginning characters represent the IP address of the client in big-endian long and the two last the port in big-endian short
    //
    //Else
    // array(
    //  array(
    //      'peer_id' => ... //Peer ID (ignored if $no_peer_id)
    //      'ip' => ...      //IP address of the peer
    //      'port' => ...    //Port used by the peer
    //  )
    // )
    public function getPeers($info_hash, $peer_id, $compact = false, $no_peer_id = false){
        $requete = $this->_bdd->prepare('SELECT peer_id, INET_NTOA(ip_address) AS ip_address, port FROM tracker_peers 
                                         WHERE info_hash = :info_hash AND peer_id != :peer_id AND (expires IS NULL OR expires > NOW())');
        
        list($info_hash, $peer_id) = $this->prepare(array($info_hash, $peer_id));
        $requete->bindValue(':info_hash', $info_hash);
        $requete->bindValue(':peer_id', $peer_id);
        $requete->execute();
        
        if($compact){
            $return = '';
            while($row = $requete->fetch()){
                $return .= pack('N', ip2long($row['ip_address']));
                $return .= pack('n', intval($row['port']));
            }
        }
        else{
            $return = array();
            while($row = $requete->fetch()){
                $peer = array(
                    'ip' => $row['ip_address'],
                    'port' => $row['port'],
                );
                if(!$no_peer_id){
                    $peer['peer id'] = $row['peer_id'];
                }
                $return[] = $peer;
            }
        }
        
        $requete->closeCursor();
        return $return;
    }

    //Return leech/seed stats of a torrent
    public function getPeerStats($info_hash, $peer_id){
        $requete = $this->_bdd->prepare('SELECT COALESCE(SUM(status = "complete"), 0) AS complete,
                                                COALESCE(SUM(status != "complete"), 0) AS incomplete
                                         FROM tracker_peers 
                                         WHERE info_hash = :info_hash AND peer_id != :peer_id AND (expires IS NULL OR expires > NOW())');
        list($info_hash, $peer_id) = $this->prepare(array($info_hash, $peer_id));
        $requete->bindValue(':info_hash', $info_hash);
        $requete->bindValue(':peer_id', $peer_id);
        $requete->execute();
        $results = $requete->fetch();
        $requete->closeCursor();

        return $results;
    }
}
