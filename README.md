### ews [easy websocket]

1. 该项目基于workerman.gateway,仅可用于基于linux内核的操作系统
2. php start.php start -d (服务器端口可通讯)
3. 心跳json数据包 {"uri":"ws#ping"}
4. 给所有人发消息json数据包 {"uri":"ws#send_to_all","content":"消息内容"}

###### 详细文档请参考 [GatewayWorker手册](http://doc2.workerman.net/) 
