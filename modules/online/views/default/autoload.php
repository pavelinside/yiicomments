<pre>
см. AutoloadService.php

loadClass для подключения php файлов
spl_autoload_register(array($this,'loadClass'));

        $this->service->register();
        $this->service->setPublicURL('publicUrl');
        $this->service->setPublicPath('publicPath');
        $this->service->addNamespace('Foo\Bar', '/path/to/packages/foo-bar/src');

LoadPublic использовалась для подключения js, css файлов
</pre>
