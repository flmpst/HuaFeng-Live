# V1 API 文档

*以下 $api 均为 /api/v1*

*请求每个 API 时POST参数中必须有 token 参数 token 可在个人设置面板里面的开发者选项中复制*

## 直播

### 获取列表
``GET``  $api/live/list

### 获取指定直播间详细信息
``GET`` $api/live/get?live_id=直播间id

### 创建直播间
``POST``  $api/live/create
必填：

1. description 描述
2. name 名字
3. videoSource 直播源
4. videoSourceType 直播源类型
5. 可选：
  1. pic 封面，如果不填则加载 /StaticResources/image/Image_330346604143.png

6. 允许的直播源类型：
  1. flv
  2. mp4
  3. m3u8


### 删除直播间
``GET`` $api/live/delet?liveId=直播间id

### 更新直播间信息
``POST`` $api/live/update
传递参数与上述创建直播间相符

### 直播间聊天
### 获取
``GET`` $api/chat/get?room_id=直播间id
可选GET参数：
``offset`` 偏移量 默认0
``limit`` 限制返回的数量 默认10

### 发送
``POST`` $api/chat/send?room_id=直播间id
message:消息内容

### 获取指定直播间消息总数
``GET`` $api/chat/count?room_id=直播间id



## 用户

### 获取当前登录的用户信息

``GET`` $api/user/get

### 验证

``POST`` $api/user/captcha

请使用极验请求，成功后会返回验证token，服务端会保存token到PHP SESSION 中

### 登录、注册

``POST`` $api/user/auth

*注：调用此api前先验证入机*

captcha_token: 入机验证token

email: 邮箱

password: 密码(明文)

### 更新用户信息

``POST`` $api/user/update

username： 用户名

### 验证邮箱

``GET`` $api/user/verifyEmail?token=发送到邮箱的token

### 退出登录

``GET`` $api/user/logout

### 获取新的token

``GET`` $api/refresh



