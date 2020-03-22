<?php
/**
 * 开发者中心默认Layout
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Ocara框架开发者中心</title>
    <?php ocImport($this->getViewPath('css/content.php'), true, false); ?>
</head>
<body>
<div class="main">
    <?php $this->showTpl(); ?>
</div>
</body>
</html>