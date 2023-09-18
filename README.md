
## 改造

- 去除默认前端
  - `index/index/index` 屏蔽跳转
  - `config.php` 去除admin禁止访问限制
- `common/controller/api.php` 使用 `Help.php`
- 默认引入支付、登录背景、富文本三个插件
- 默认控制器文本 `Api/controller/Demo2.php`
- 全局函数引用 `ifunc.php`
- 在默认SQL文件增加数据表`fa_user_account` application/admin/command/Install/fastadmin.sql