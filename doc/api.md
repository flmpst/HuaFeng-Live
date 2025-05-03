# 花枫Live V1 API 文档

## 基础信息

- **API路径前缀**：`/api/v1`
- **请求要求**：必须包含 `token` 参数可以通过POST请求传递Token也可以使用Authorization: Bearer your_token_here（获取方法请参阅Token节）

### 基本返回结构

```json
{
    "APIVersion": "1.2.0.0",
    "code": 200,
    "message": true,
    "data": {}
}
```

---

## Token

### 获取Token

1. 通过 `/refresh?method=refresh` 接口获取Token
2. 通过 `/verify/client` 进行第三方客户端授权可获得专用Token

**注意：**
- 每次调用 `/refresh` 会生成新Token，旧Token失效（第三方客户端Token除外）
- 第三方客户端Token独立且有效期为365天

### GET 使用第三方客户端申请Token

`/verify/client?callback={回调地址}&clientid={客户端ID}`

#### 请求参数

| 参数     | 类型   | 必填 | 描述                                                                                                     |
| -------- | ------ | ---- | -------------------------------------------------------------------------------------------------------- |
| callback | URL    | 是   | 回调地址，返回格式：`{callback}?succeed={bool}&msg={授权详细结果}&token={token}`                         |
| clientid | string | 是   | 客户端ID，建议格式：`<平台类型>-<设备标识>-<随机后缀>`（如：`web-chrome-abc123` 或 `ios-iphone-def456`） |

---

## 直播

### GET 获取直播列表

`/live/list`

#### 返回示例

```json
{
    "APIVersion": "1.2.0.0",
    "code": 200,
    "message": true,
    "data": {
        "list": [
            {
                "id": 唯一ID,
                "name": "直播间名",
                "pic": "直播间封面（null表示不存在）",
                "status": "状态",
                "author": "主播名",
                "authorAvatar": "主播头像链接",
                "peoples": 三分钟内观看的用户数量,
                "description": "直播间描述"
            }
        ]
    }
}
```

### GET 获取指定直播间详细信息

`/live/get?live_id={直播间id}`

#### 请求参数

| 参数    | 类型    | 必填 | 描述     |
| ------- | ------- | ---- | -------- |
| live_id | integer | 是   | 直播间ID |

#### 返回示例

```json
{
    "APIVersion": "1.2.0.0",
    "code": 200,
    "message": true,
    "data": {
        "id": 唯一ID,
        "user_id": 主播用户ID,
        "name": "直播间名",
        "pic": "直播间封面（null表示不存在）",
        "description": "直播间描述",
        "css": "直播间自定义CSS",
        "status": "状态",
        "videoSource": "直播源链接",
        "videoSourceType": "直播源类型（m3u8、flv、mp4）",
        "author": "主播名",
        "authorAvatar": "主播头像链接"
    }
}
```

### POST 创建直播间

`/live/create`

#### 请求参数

| 参数            | 类型   | 必填 | 描述                         |
| --------------- | ------ | ---- | ---------------------------- |
| description     | string | 是   | 直播间描述                   |
| name            | string | 是   | 直播间名称                   |
| videoSource     | string | 是   | 直播源                       |
| videoSourceType | string | 是   | 直播源类型（flv、mp4、m3u8） |
| pic             | string | 否   | 封面图片URL（默认图片为空）  |

#### 返回示例

```json
{
    "APIVersion": "1.2.0.0",
    "code": 200,
    "message": "创建成功",
    "data": {
        "id": 唯一ID
    }
}
```

### GET 删除直播间

`/live/delet?liveId={直播间id}`

#### 请求参数

| 参数   | 类型    | 必填 | 描述             |
| ------ | ------- | ---- | ---------------- |
| liveId | integer | 是   | 要删除的直播间ID |

#### 返回示例

```json
{
    "APIVersion": "1.2.0.0",
    "code": 200,
    "message": "删除成功",
    "data": []
}
```

### POST 更新直播间信息

`/live/update`

#### 请求参数

与创建直播间相同

#### 返回示例

```json
{
    "APIVersion": "1.2.0.0",
    "code": 200,
    "message": "更新成功",
    "data": []
}
```

---

## 直播间聊天

### GET 获取聊天消息

`/chat/get?room_id={直播间id}`

#### 请求参数

| 参数    | 类型    | 必填 | 描述                   |
| ------- | ------- | ---- | ---------------------- |
| room_id | integer | 是   | 直播间ID               |
| offset  | integer | 否   | 偏移量（默认0）        |
| limit   | integer | 否   | 限制返回数量（默认10） |

#### 返回示例

```json
{
    "APIVersion": "1.2.0.0",
    "code": 200,
    "message": true,
    "data": {
        "onlineUsers": [
            {
                "user_id": 用户ID,
                "avatar_url": "用户头像",
                "last_time": 上次在线时间（时间戳格式）
            }
        ],
        "messages": [
            {
                "id": 消息ID,
                "type": "消息类型",
                "content": "消息内容",
                "created_at": "发送时间（标准格式）",
                "status": "状态",
                "username": "发送消息用户名",
                "avatar": "用户头像"
            }
        ]
    }
}
```

### POST 发送聊天消息

`/chat/send?room_id={直播间id}`

#### 请求参数

| 参数    | 类型    | 必填 | 描述     |
| ------- | ------- | ---- | -------- |
| room_id | integer | 是   | 直播间ID |
| message | string  | 是   | 消息内容 |

#### 返回示例

```json
{
    "APIVersion": "1.2.0.0",
    "code": 200,
    "message": true,
    "data": []
}
```

---

## 用户

### GET 获取当前登录用户信息

`/user/get`

#### 返回示例

```json
{
    "APIVersion": "1.2.0.0",
    "code": 200,
    "message": true,
    "data": {
        "user_id": 用户ID,
        "username": "用户名",
        "password": "哈希加密后的密码",
        "email": "注册邮箱",
        "register_ip": "注册IP",
        "group_id": 用户组ID,
        "created_at": "注册时间",
        "status": 状态,
        "sets": [自定义设置],
        "token": "网页登录Token"
    }
}
```

### POST 更新用户信息

`/user/update`

#### 请求参数

| 参数     | 类型   | 必填 | 描述     |
| -------- | ------ | ---- | -------- |
| username | string | 是   | 新用户名 |

#### 返回示例

```json
{
    "APIVersion": "1.2.0.0",
    "code": 200,
    "message": true,
    "data": {}
}
```

---

## 公用错误响应

| HTTP 状态码 | 错误信息           | 描述                            |
| ----------- | ------------------ | ------------------------------- |
| 400         | Invalid API method | API方法名称无效或未传递完整数据 |
| 401         | 验证未通过         | 某些认证未通过或用户未登录      |
| 406         | 方法不存在         | 请求的API方法不存在             |
| 500         | 内部错误           | 系统错误，请联系站长            |

---

## 更新日志

- **2025 年 4 月 2 日 v1.1 更新**
  - 删除 `/user/verifyEmail`，替换为 `/verify/email`
  - 添加 `/search/`

- **2025 年 4 月 3 日 v1.2 更新**
  - 添加 `/user_settings`

- **2025 年 5 月 2 日 v1.2 更新**
  - 添加 `/verify/email`
  - 添加 `/verify/client`
  - API 文档完全完善
- 2025 年 5 月 3 日 v1.2.1.0 更新
  - 现已可通过请求头发送 Token 格式：Authorization: Bearer your_token_here