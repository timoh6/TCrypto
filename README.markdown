TCrypto
=======

TCrypto is a simple PHP 5.3+ key-value storage framework for use in storing
data securely to a "storage backend". By default, a cookie will be used.

TCrypto can be used as a scalable "session handler", especially scalable,
if cookies are used as a storage. This is a bit like Ruby on Rails sessions.

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
    // this makes it possible to use cookies even there is no HTTPS connection,
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
    $options = array('mac_key' => 'f€ftä=Dt...', 'cipher_key' => 'frVqÅ2#...');
    
    // Inject the needed dependencies.
    $tc = new TCrypto\Crypto($storage, $plugins, $crypto, $options);
    
    // Value can be any serializable data type. 
    $tc->setValue('key', 'value');
    $tc->setValue('object', new stdClass());
    $tc->removeValue('object');
    
    echo $tc->getValue('key'); // "Value"
    echo $tc->getValue('object'); // "NULL"
    
    // Saves the data to a storage.
    $tc->save();
    
    // Destroys the data both from memory and storage.
    $tc->destroy();

Plugins
-------

TCrypto comes with a simple "plugin system". Plugins are run in two separate
places.

First:  before saving the data to a storage.
Second: after extracting the data from a storage (in reverse order).

This creates the needed serialize/unserialize plugin.
    $plugins = new TCrypto\PluginContainer();

You can also attach more plugins.
    $plugins->attach(new TCrypto\Plugin\CompressPlugin());
Now the data will be first serialized and then compressed (when saving).
When extracting the data from a storage, the data will be first uncompressed
and then unserialized.

SECURITY NOTE:
When extracting the data from a storage, a HMAC value will be checked BEFORE
the plugins are run. This could potentially lead to bugs or security issues.
If you use any extra plugins, make sure they operate correctly.