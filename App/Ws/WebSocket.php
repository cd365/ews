<?php


namespace App\Ws;


use GatewayWorker\Lib\Gateway as GateWayWorker;

/**
 * 服务端自身处理Gateway的逻辑
 * Class WebSocket
 * @package app\ws
 */
class WebSocket
{

    /**
     * 绑定cid和uid
     * @param string $clientId
     * @param int $uid
     * @return bool
     */
    public static function BindUid(string $clientId='',int $uid=0) : bool {
        if ($clientId == '' || $uid == 0){
            return false;
        }
        // 不在线
        if (GateWayWorker::isOnline($clientId) == 0){
            return false;
        }
        // 绑定
        GateWayWorker::bindUid($clientId,$uid);
        // 当前uid所有被绑定的cid
        $clientIds = GateWayWorker::getClientIdByUid($uid);
        if ([] == $clientIds){
            return false;
        }
        $len = count($clientIds);
        for ($i=0;$i<$len;$i++){
            // 不是当前的cid,全部踢掉
            if ($clientIds[$i] != $clientId){
                GateWayWorker::closeClient($clientIds[$i]);
                continue;
            }
        }
        $guid = GateWayWorker::getUidByClientId($clientId);
        // 没有绑定成功的情况
        if (is_null($guid)){
            return false;
        }
        // 绑定的uid不是当前设置的uid
        if ($guid != $uid){
            return false;
        }
        return true;
    }

    /**
     * 取消绑定cid和uid
     * @param string $clientId
     * @param int $uid
     * @return bool
     */
    public static function UnBindUid(string $clientId='',int $uid=0) : bool {
        if ($clientId == '' || $uid == 0){
            return false;
        }
        // 取消绑定
        GateWayWorker::unbindUid($clientId,$uid);
        // 返回null,说明没有绑定的关系
        $guid = GateWayWorker::getUidByClientId($clientId);
        if (!is_null($guid)){
            return false;
        }
        return true;
    }

}