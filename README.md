IRCBot-PHP
==========

IRCBot-PHP is an IRC bot framework written on top of the
[Modfwango](http://modfwango.com) socket framework.  This bot is meant to be
extended upon by
[Modfwango-compliant](https://github.com/Modfwango/Modfwango#development)
modules to incorporate customized functionality, although, this project does
provide several built-in modules custom tailored to IRC bots.

Modules
=======

A brief overview of built-in modules is listed below.

#### admin/ChannelManagement
This module allows you to control auto-joined channels upon network connection.
The following command syntax is accepted:

```
[botnick][separator] [(auto)join|(auto)part] [channel]
```

The separator can literally be any character.

A few examples:

Automatically join `#lastfm` upon connection.
```
nowplaying, autojoin #lastfm
```

Temporarily part `#lastfm`.
```
nowplaying, part #lastfm
```

#### admin/ConnectionControl
This module allows you to control the state of configured connections (files
inside the `conf/connections` directory).  The following command syntax is
accepted:

```
[botnick][separator] connection [(re|un)load] [connection_name]
```

The separator can literally be any character.

A few examples:

Disconnect from the network named `freenode`.
```
nowplaying, connection unload freenode
```

Connect to the network named `freenode`.
```
nowplaying, connection load freenode
```

#### admin/Eval
This module, if you so choose to keep it auto-loaded, will allow you to execute
PHP code with a command.  The following command syntax is accepted:

```
[botnick][separator] eval [string]
```

The separator can literally be any character.

A few examples:

Return the current system (unix) time.
```
nowplaying, eval return time();
```

Manually unload a module.
```
nowplaying, eval return ModuleManagement::unloadModule("Eval");
```

#### admin/MemoryUsage
This module returns the current memory usage by the bot's PHP process.  The
following command syntax is accepted:

```
[botnick][separator] memusage
```

#### admin/ModuleControl
This module allows you to control the current set of loaded modules.  The
following command syntax is accepted:

```
[botnick][separator] module [(re|un)load] [module_name]
```

The separator can literally be any character.

A few examples:

Unload the module named `Eval`.
```
nowplaying, module unload Eval
```

Load the module named `admin/Eval`.
```
nowplaying, module load admin/Eval
```

#### admin/Power
This module allows you to control the state of the bot's process.  The following
command syntax is accepted:

```
[botnick][separator] [restart|stop]
```

The separator can literally be any character.

A few examples:

Stop the bot.
```
nowplaying, stop
```

Restart the bot.
```
nowplaying, restart
```

#### admin/Uptime
This module returns the current uptime of the bot's PHP process.  The following
command syntax is accepted:

```
[botnick][separator] uptime
```
