# Ocara


复杂概念设计简单化，分散知识积累抽象化！皆源自效率设计。

Ocara是最新一代开源PHP框架，结合**领域驱动设计**和依赖注入模式。

除了最基本功能外，提供**事务嵌套、多环境配置、分库分表、默认条件、字段别名、常量对象、实体模型、缓存模型化**等创新设计，支持API、Restful开发。

官网地址：http://www.ocara.cn

最新实例下载：https://github.com/ocaraworks/ocara-php-demos

最新手册下载：http://ocara.cn//attachments/download/manual/2.0/20200303/Ocara2.0_manual.zip

## 1、安装框架

   （1）composer安装
   
       （a）安装composer
            
            请到https://www.phpcomposer.com/下载安装

       （b）安装框架
            
            如果没有初始化composer目录，请在目录执行：
                  composer init
           
            如果安装最新版：
    
                  composer require ocaraworks/ocara-php dev-master

            如果安装2.0版：
 
                  composer require ocaraworks/ocara-php 2.0
            
            然后安装：
                  composer install
            或更新：
                  composer update

   （2）手动安装
   
           请下载框架并解压，在index.php指定好路径即可。

    
## 2、自动生成项目目录

   （1）复制文件
       
        请复制demo/myapp下面的目录和文件放到项目根目录，并将public目录设置为网站根目录。

   （2）修改框架文件路径和控制器模式

         修改框架路径
          
          //require_once dirname(dirname(__DIR__)) . '/ocara/system/library/Core/Ocara.php'; //手动安装改这里
          require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php'; //composer安装改这里
      
         修改控制器模式：
         
            默认是同步渲染模式，还可以设置api、rest分别表示API和Restful模式。
            比如api模式，要将Ocara::create()改成Ocara::create('api')，不改则是同步渲染模式common。
      
                 /**
                  * 运行应用
                  */
                  Ocara::create('api');


   （3）项目生成
      
         在浏览器打开网站，访问public/index.php，比如：http://localhost。会自动新建项目目录，提示：Application generate Success!

## 3、使用说明

   （1）添加控制器动作
   
       框架自动新建了home/index控制器路由，还可以进入开发者中心添加更多，访问http://localhost/pass/tools进入。

   （2）伪静态生成

       Apache服务器，框架会自动在public添加一个.htaccess文件写好了伪静态。

       Nginx服务器，需要自己改，可参考以下配置：
           
       server {
        listen        80;
        server_name  mytest.lc;
        root   "D:/wwwroot/mytest/public";
        
        location / {
            index index.php index.html error/index.html;
            try_files $uri $uri/ @defaults;
            autoindex  off;
        }
		
        location @defaults {
            rewrite ^/pass(.*)$ /pass$1;
            rewrite ^/(.*)$ /index.php?$1;
        }		

        location ~ \.php(.*)$ {
            fastcgi_pass   127.0.0.1:9001;
            fastcgi_index  index.php;
            fastcgi_split_path_info  ^((?U).+\.php)(/?.+)$;
            fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
            fastcgi_param  PATH_INFO  $fastcgi_path_info;
            fastcgi_param  PATH_TRANSLATED  $document_root$fastcgi_path_info;
            include        fastcgi_params;
        }
    }
