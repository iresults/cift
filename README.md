Cift
====

> Send emails. Nothing else.


Installation
------------

To install **globally** run:

```bash
composer.phar global require iresults/cift
```

and make sure that the composer bin path is in your $PATH

``bash
export PATH=$PATH:~/.composer/vendor/bin
``


Usage
-----

### Send a short mail

```bash
bin/cift recipient@domain.tld sender@domain.tld subject "A nice message from me"
```


### Send a longer mail

```bash
bin/cift recipient@domain.tld sender@domain.tld subject < newsletter.html
```

If the body contains an `<` the message will be sent with content type `text/html`.


### Send to multiple recipients

```bash
bin/cift recipient@domain.com,recipient2@domain.com sender@domain.tld subject "Hello"
```
