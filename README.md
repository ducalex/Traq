Traq
======

Traq is a PHP powered project manager, capable of tracking issues for multiple projects with multiple milestones.

Requirements
------------

- PHP 5.4+
- MySQL or SQLite3
- Apache mod_rewrite or server configured to use `index.php` as the 404 page.

Installation
------------

In your browser, open the location you placed Traq in and follow the installation steps.

If you aren't using Apache you will need to configure rewriting. See the following example for nginx:

`````
if ($uri !~ ^/traq/(install|assets|asset\.php)(/|$)) {
    rewrite "^/traq/(.+)$" /traq/index.php last;
}
`````

Licenses
-------

* Traq is released under the GNU GPL license, _version 3 only_.
* Avalon is released under the GNU Lesser GPL license, _version 3 only_.
* Nanite is released under the GNU Lesser GPL license, _version 3 only_.

### Terminated Licenses ###

Licenses _permanently_ terminated:

* **devxdev / Devon Hazelett**:
  Files, classes and functions were taken and completely stripped of copyright,
  warranty and code comments then used in the "Soule Framework".

* **burnpiro / Kemal Erdem and michalantoszczuk**:
  Traq was forked and all references to Traq in each files copyright headers was
  removed and replaced with "Shelu".

Contributors
------------

A list of people who contribute or have contributed to Traq can be found on [Github](https://github.com/nirix/traq/graphs/contributors).

Credits
-------

- Most icons by [famfamfam.com](http://famfamfam.com). All rights reserved.
- Some icons by [Yusuke Kamiyamane](http://p.yusukekamiyamane.com). All rights reserved.
