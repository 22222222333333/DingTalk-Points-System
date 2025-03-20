# DingTalk-Points-System
基于AI编写的一个简易钉钉积分系统

先叠甲，大部分代码基于GROK 3完成，只保证功能能用，不保证代码没有bug和冗余

syncEmployees.php：用于同步通讯录
$corpId = '';
$appKey = '';
$appSecret = '';
需要在里面填写这三个参数

getUserInfo.php 用于用户验证，也可免登
$corpId = '';
$appKey = '';
$appSecret = '';
这三个参数也要填

db_config.php 填写数据库信息

checkAdmin.php 后台的管理员验证，也需要填三个参数
$corpId = '';
$appKey = '';
$appSecret = '';

index.html中也有部分需要填写corpId，自行搜索

数据库结构文件在jifen_hantwo_cn.sql里面

![image](https://github.com/user-attachments/assets/4346ea2d-56d1-44be-8b9e-2e764b3c4e85)
![image](https://github.com/user-attachments/assets/d2430849-a02c-4908-a0cc-e16227d7c5d9)

