### ews [easy websocket]

1. 该项目基于workerman.gateway,仅可用于基于linux内核的操作系统
2. composer install
3. php start.php start -d (服务器端口可通讯)
4. 心跳json数据包 {"Uri":"Ws#Ping"}
5. 给所有人发消息json数据包 {"Uri":"Ws#SendToAll","Content":"消息内容"}

###### 详细文档请参考 [GatewayWorker手册](http://doc2.workerman.net/) 
