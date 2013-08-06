About
=====

[![Build Status](https://secure.travis-ci.org/timoh6/TCrypto.png)](http://travis-ci.org/timoh6/TCrypto)

TCrypto is a simple and flexible PHP 5.3+ in-memory key-value storage library. By default,
a cookie will be used as a storage backend.

TCrypto has been designed from the ground up with security in mind. Safe algorithms
and modes. Automatic and safe initialization vector creation. Encryption and
authentication key creation (Keytool) using strong randomness. Key rotation
(versioned keys).

TCrypto can be used as a scalable "session handler". Especially scalable,
if cookies are used as a storage backend. This is a bit like Ruby on Rails sessions.

There's an article outlining basic TCrypto usage at [websec.io](http://websec.io/): [TCrypto: Encrypted data storage for PHP applications](http://websec.io/2012/08/29/TCrypto-Encrypted-Data-Storage-for-PHP.html)

This is a preview release (although considered stable). Keyfile encryption will be
available in version 1.0.0. Otherwise no other changes are planned between current
dev-master and 1.0.0.

**Important security note about data compression:**

Prior to August 6 2013, TCrypto allowed to use data compression when the data was
also being encrypted. This may lead to disclosure of the (confidential) plain text.
Every TCrypto users are encouraged to update to the current dev-master. Especially
if CompressPlugin() was used with data encryption.


TCrypto is placed in the public domain.


Requirements
------------

TCrypto requires PHP version 5.3 or greater. To use encryption, either Mcrypt or
OpenSSL extension must be available. If OpenSSL is used for encryption,
PHP 5.3.3 or greater is required. Optional compression plugin requires zlib.


TCrypto Keymanager and Keytool
------------------------------

Keytool is a small command-line application, which is used to create encryption
and authentication keys. Keytool can also be used to remove inactive keys.

Before you start using TCrypto, run Keytool and create your first set of keys:

    $ cd /path/to/TCrypto/bin
    $ php keytool

Make sure the right keyfile is selected (if necessary, change keyfile) and select
option 1 "Add new keys".

To remove inactive keys, run Keytool and select option 2 "Remove inactive keys".
You should always remove inactive keys before you add new keys. This is because
inactive keys are determined comparing the (current) primary key timestamp and key
lifetime (keys that can not be active are removed).

There are a few Keytool settings you can tweak.
File `TCrypto/bin/keytool.config.php`:

- **'keyfile_permissions'**: the default filesystem permissions for the keyfile
- **'bytes_in_key_name'**: key name length
- **'key_max_lifetime'**: this should be the same as $_macMaxLifetime in TCrypto
- **'keyfile_location'**: the default keyfile location

If you remove or comment out 'keyfile_permissions' setting, chmod() won't be run at
all for the keyfile.


Keytool stores keys as a plain PHP array. The key format is as follows:

``` php
<?php
array (
  'tcrypto_key_data' => 
  array (
    'keys' => 
    array (
      'index_xxx' => 
      array (
        'encryption' => 'key_xxx',
        'authentication' => 'key_xxx',
        'time' => xxx,
      ),
    ),
    'meta_data' => 
    array (
      'primary_index' => 'index_xxx',
      'last_key_creation_time' => xxx,
    ),
  ),
);
```

'index_xxx' means a unique array index. Keytool will (by default) use 3 bytes of
entropy in a key name (hexadecimal format). 3 bytes can produce about 8 million
distinct keys without collisions. Given that you should probably have no more
than a few keys in your keyfile, 3 bytes of entropy in a key name will be
easily enough.

'meta_data' is used to identify the current primary key index.

There can be multiple keys in the 'keys' array. However, you should remove
constantly inactive keys (to avoid filling your key file with "junk"). It is
a good idea to always remove inactive keys before you add new keys.


Examples
--------

``` php
<?php
require '/path/to/library/TCrypto/Loader.php';
$loader = new TCrypto\Loader();
$loader->register();
// Or more simply, use Composer http://getcomposer.org/download/
// Add something like "tcrypto/tcrypto": "@dev" to your composer.json:
```
``` json
{
    "require": {
        "tcrypto/tcrypto": "@dev"
    }
}
```
``` php
<?php
// Keymanager handles encryption/authentication keys. By default, Filesystem()
// method looks the keyfile from `TCrypto/Keystore/default` file. If you want to use
// another keyfile, simply give the full path of the keyfile to Filesystem()'s
// constructor. E.g.
// $keymanager = new TCrypto\KeyManager\Filesystem('/path/to/keyfile');
$keymanager = new TCrypto\KeyManager\Filesystem();
// You can also inject the key data as a plain PHP array using setKeysAsArray():
// $keymanager->setKeysAsArray(array('tcrypto_key_data'...));

// The data will be stored to a cookie.
$storage = new TCrypto\StorageHandler\Cookie();
// You can pass a boolean false as the first parameter for Cookie,
// this makes it possible to use cookies even if there is no HTTPS connection,
// although this is not recommended.
// The second parameter for Cookie specifies the name of the cookie.
// $storage = new TCrypto\StorageHandler\Cookie(false, 'my_cookie_name');

// or, to get TCrypto payload immediately back as a string:
// $storage = new TCrypto\StorageHandler\String();
// $tcryptoPayload = $tcrypto->save();
// To feed back previously used TCrypto payload, pass it as a first argument to String():
// $storage = new TCrypto\StorageHandler\String($tcryptoPayload);

// In short, you can save TCrypto payload to, say, a database
// (without the need to use a specific database StorageHandler).
// Then you can fetch the TCrypto payload from the database, and
// feed it back into TCrypto.
// NOTE, using String() StorageHandler will make
// $tcrypto->save() to output the actual payload.

// Initialize encryption using either OpenSSL or Mcrypt (optional).
$crypto = new TCrypto\CryptoHandler\OpenSslAes128Cbc();
// or
$crypto = new TCrypto\CryptoHandler\OpenSslAes256Cbc();
// or
$crypto = new TCrypto\CryptoHandler\McryptAes128Cbc();
// or
$crypto = new TCrypto\CryptoHandler\McryptAes256Cbc();

// Initialize the default plugin (serialize/unserialize).
$plugins = new TCrypto\PluginContainer();

// Attach an extra plugin (compress/uncompress) (optional).
$plugins->attachPlugin(new TCrypto\Plugin\CompressPlugin());

// Available options:
// (array) 'entropy_pool'
// (int) 'max_lifetime'
// (bool) 'save_on_set'
$options = array('max_lifetime' => 6400);

// Create a new Crypto instance and inject the needed dependencies.
$tcrypto = new TCrypto\Crypto($keymanager, $storage, $plugins, $crypto, $options);

// If you create a new TCrypto instance without passing any of the dependencies,
// e.g. $tc = new TCrypto\Crypto(), TCrypto will use the following defaults:
// $keyManager = new KeyManager\Filesystem(); // Uses the default keyfile.
// $storage = new StorageHandler\Cookie(); // Requires HTTPS connection.
// $plugins = new PluginContainer(); // Default serialize/unserialize plugin.
// $crypto = null // No encryption.
// $options = array() // No options are modified.

// Value can be any serializable data type. 
$tcrypto->setValue('key', 'value');
$tcrypto->setValue('object', new stdClass());
$tcrypto->removeValue('object');

echo $tcrypto->getValue('key'); // "value"
echo $tcrypto->getValue('object'); // "NULL"

// Saves the data to a storage.
$tcrypto->save();
// If String() StorageHandler is being used, $tcrypto->save() will output
// the TCrypto payload (you need to store the payload by some other means).

// Destroys the data both from memory and storage.
$tcrypto->destroy();
```


About symmetric encryption
--------------------------

Currently there are four choices for an encryption provider:

    Crypto\CryptoHandler\OpenSslAes128Cbc
    Crypto\CryptoHandler\McryptAes128Cbc

and

    Crypto\CryptoHandler\OpenSslAes256Cbc
    Crypto\CryptoHandler\McryptAes256Cbc

OpenSslAes128Cbc/McryptAes128Cbc and OpenSslAes256Cbc/McryptAes256Cbc both
implement AES in CBC mode using a random initializing vector. Only the key size
differs between them. 128-bit key size should be unbreakable with foreseeable
technology. But on the other hand, 256-bit keys provides more margin of security
(against side channels etc.). Encrypting with 128-bit keys should be somewhat
faster than encrypting with 256-bit keys. However, in a typical web application
usage scenario, this speed difference is probably insignificant.

If you feel paranoid (the bigger, the better fetish), use McryptAes256Cbc.
Otherwise use McryptAes128Cbc.

TCrypto derives encryption keys from variable data (timestamps, initializing
vector, key seeds and user supplied extra entropy sources). This guarantees
that a fresh and random key will be used for each encryption operation. The key
setup combined with (truncated) SHA512 hashing ensures (currently known) related-key
attacks does not apply against AES-256 (McryptAes256Cbc).

If your system supports OpenSSL, use OpenSSL based encryption (OpenSslAes128Cbc
or OpenSslAes256Cbc).


Plugins
-------

TCrypto comes with a simple "plugin system". Plugins are run in two separate
places.

* Before saving the data to a storage.

* After extracting the data from a storage (plugins are run in reverse order).

It is important to note that you can not use data compression plugins if you
are using data encryption.

If you configure TCrypto to use data compression at the same time data is
being encrypted, the PluginContainer will automatically skip data compression plugins.

This is important because compressing confidential (encrypted) data may leak information
about the plain text.

This creates the required serialize/unserialize plugin:

``` php
<?php
// PluginContainer() automatically attaches DefaultPlugin(), which handles
// serializing and unserializing.
$plugins = new TCrypto\PluginContainer();
```

You can also attach more plugins:

``` php
<?php
// NOTE: CompressPlugin will not be run if data encryption is being used.
// This is because data compression leaks information about the plain text.
$plugins->attachPlugin(new TCrypto\Plugin\CompressPlugin());
```

Now the data will be first serialized and then compressed (when saving).
When extracting the data from a storage, the data will be first uncompressed
and then unserialized.

NOTE: $plugins->attach() is not supported anymore. Use $plugins->attachPlugin()
instead of $plugins->attach().


Security notes
--------------

### Keystore

TCrypto Keytool will write all encryption/authentication keys to a
filesystem. Make sure this keyfile can not be read by anyone else. It is
probably a good idea to use chmod 0600 setting for the keyfile
(depending on your server settings).

### Cookies as a storage backend

By default, TCrypto uses browser cookies as a "permanent" storage backend. If you
do not use encryption, anyone who you send the data can read (but not modify)
it. It is up to you to decide does the data need to be encrypted.

It should be pointed out that TCrypto does not address replay of old cookies.
Legitimate client can send previous (old) cookies within the "max_lifetime"
period. 

HTTPS connection is another important concern about browser cookies. If there is
no "secure connection", the data may be captured (and replayed) by whoever is
eavesdropping on the line.

### Plugins

When extracting the data from a storage, an HMAC value will be checked BEFORE
the plugins are run. This could potentially lead to bugs or security issues.
If you use any extra plugins, make sure they operate correctly.


Issues or questions?
--------------------

Mail me at timoh6@gmail.com or use GitHub.