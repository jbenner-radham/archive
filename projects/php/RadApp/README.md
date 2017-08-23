## This is a super lazy summarization of how to use RadApp...

```php
<?php

require_once 'class.RadApp.php';

$app = new RadApp();

echo $app->js(['jquery', '//netdna.bootstrapcdn.com/twitter-bootstrap/2.2.2/js/bootstrap.min.js'])
          ->css(['style'])
          ->header(['title' => 'Hello World!']);

?>

<body>
	
	<div>Stuff is cool & stuff!</div>

</body>

<?php

echo $app->footer();

unset($app);

?>
```
## Super early Haml support...

#### Haml
```haml
-# yolo.haml
.yolo Hi Thar!
#nolo Hi Thar Too!
```

#### PHP
```php
<?php

# haml.php

require_once 'class.RadApp.php';

$app = new RadApp();

$app->haml('yolo'); # You can use 'yolo.haml' as well but it will auto-append '.haml'.

unset($app);

?>
```

#### (Generated) HTML
```html
<div class="yolo">Hi Thar!</div>
<div id="nolo">Hi Thar Too!</div>
```

...and yes the use of "YOLO" is _ironic_ (or stupid, or stupidly awesome, etc.).
