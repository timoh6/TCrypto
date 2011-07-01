TCrypto
=======

TCrypto is a simple and flexible PHP 5.3+ key-value storage library. By default,
a cookie will be used as a storage backend.

TCrypto has been designed from the ground up with security in mind.

TCrypto can be used as a scalable "session handler". Especially scalable,
if cookies are used as a storage backend. This is a bit like Ruby on Rails sessions.

TCrypto takes care of checking data integrity, encryption and
storing/retrieving data from the selected storage implementation.

This is a preview release.

TCrypto is placed in the public domain.


Examples
--------

    require '/path/to/library/TCrypto/Loader.php';
    $loader = new TCrypto\Loader();
    $loader->register();
    
    // The data will be stored to a cookie.
    $storage = new TCrypto\StorageHandler\Cookie();
    
    // You can pass a boolean false as the first parameter for Cookie,
    // this makes it possible to use cookies even if there is no HTTPS connection,
    // although this is not recommended.
    // The second parameter for Cookie specifies the name of the cookie.
    // $storage = new TCrypto\StorageHandler\Cookie(false, 'my_cookie_name');
    
    // Initialize encryption (optional).
    $crypto = new TCrypto\CryptoHandler\McryptAes128Cbc();
    // or
    $crypto = new TCrypto\CryptoHandler\McryptAes256Cbc();
    
    // Initialize the default plugin (serialize/unserialize).
    $plugins = new TCrypto\PluginContainer();
    
    // Attach an extra plugin (compress/uncompress) (optional).
    $plugins->attach(new TCrypto\Plugin\CompressPlugin());
    
    // mac_key and cipher_key must contain at least 40 characters (bytes).
    // mac_key must be always set. cipher_key needs to be set if encryption
    // will be used.

    // Available options:
    // (string) 'mac_key'
    // (string) 'cipher_key'
    // (array) 'entropy_pool'
    // (int) 'max_lifetime'
    // (bool) 'save_on_set'
    $options = array('mac_key' => 'f€ftä=Dt...', 'cipher_key' => 'frVqÅ2#...');
    
    // Create a new Crypto instance and inject the needed dependencies.
    $tc = new TCrypto\Crypto($storage, $plugins, $crypto, $options);
    
    // Value can be any serializable data type. 
    $tc->setValue('key', 'value');
    $tc->setValue('object', new stdClass());
    $tc->removeValue('object');
    
    echo $tc->getValue('key'); // "value"
    echo $tc->getValue('object'); // "NULL"
    
    // Saves the data to a storage.
    $tc->save();
    
    // Destroys the data both from memory and storage.
    $tc->destroy();


About symmetric encryption
--------------------------

Currently there are two choices for an encryption provider:

    Crypto\CryptoHandler\McryptAes128Cbc

and

    Crypto\CryptoHandler\McryptAes256Cbc

McryptAes128Cbc and McryptAes256Cbc both implememt AES in CBC mode using a random
initializing vector. Only the key size differs between them. 128-bit key size
should be unbreakable with foreseeable technology. But on the other hand,
256-bit keys provides more margin of security (against side channels etc.).
Encrypting with 128-bit keys should be somewhat faster than encrypting with
256-bit keys. However, in a typical web application usage scenario, this speed
difference is probably insignificant.

If you feel paranoid (the bigger, the better fetish), use McryptAes256Cbc.
Otherwise use McryptAes128Cbc.

TCrypto derives encryption keys from variable data (timestamps, initializing
vector, $_cipherKey and user supplied extra entropy sources). This quarantees
that a fresh and random key will be used for each encryption operation. The key
setup compined with (truncated) SHA512 hashing ensures (currently known) related-key
attacks does not apply against AES-256 (McryptAes256Cbc).


Plugins
-------

TCrypto comes with a simple "plugin system". Plugins are run in two separate
places.

* Before saving the data to a storage.

* After extracting the data from a storage (plugins are run in reverse order).

This creates the required serialize/unserialize plugin:

    // PluginContainer() automatically attaches DefaultPlugin(), which handles
    // serializing and unserializing.
    $plugins = new TCrypto\PluginContainer();

You can also attach more plugins:

    $plugins->attach(new TCrypto\Plugin\CompressPlugin());

Now the data will be first serialized and then compressed (when saving).
When extracting the data from a storage, the data will be first uncompressed
and then unserialized.


Security notes
--------------

Key management
~~~~~~~~~~~~~~

TCrypto does not take care of key management. It just simply uses the keys you
provide (these "master keys" are used to derive the actual HMAC and encryption
keys). You have to implement topics such as storage, safeguarding and
replacement of keys by yourself.

There is also no proper "key strength checking" in TCrypto. TCrypto will refuse
"master keys" (encryption/MAC) that are shorter than 40 bytes. Undoubtedly this
is not enough. Make sure the keys you provide contains enough entropy.

Cookies as a storage backend
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

By default, TCrypto uses browser cookies as a "permanent" storage backend. If you
do not use encryption, anyone who you send the data can read (but not modify)
it. It is up to you to decide does the data need to be encrypted.

HTTPS connection is another important concern about browser cookies. If there is
no "secure connection", the data may be captured (and replayed) by whoever is
eavesdropping on the line.

Plugins
~~~~~~~

When extracting the data from a storage, an HMAC value will be checked BEFORE
the plugins are run. This could potentially lead to bugs or security issues.
If you use any extra plugins, make sure they operate correctly.