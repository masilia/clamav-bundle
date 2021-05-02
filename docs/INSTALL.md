# Masilia ClamAv Bundle

This bundle provides an antivirus scan with clamAv for Symfony and eZ Platform formBuilder

Requirements
------------

* eZ Platform 2.5+

## Install instructions

1. Add the project repository:

   In the main `composer.json`, add:
    ```
    "repositories": [
        // ...
        {
            "type": "vcs",
            "url": "https://github.com:masilia/clamav-bundle.git"
        }
    ],
    ```

2. Install the bundle via Composer (initial installation):

    ``` bash
    $ composer require masilia/clamav-bundle
    ```

   2.1 Update the bundle via Composer if needed
    ``` bash
    $ composer update masilia/clamav-bundle
    ```

3. Activate the bundle in your `app/AppKernel.php`:

    ``` php
    $bundles = [
        ...
        new Masilia\ClamavBundle\MasiliaClamavBundle(),
        ...
    ];
    ```
4. install the clamAv modules in your server
   ```
   clamav
   clamav-daemon
   clamav-freshclam
   ```
   4.1 Under Debian distribution, run the following commands (adapt according to the distribution used):
   ```bash
    apt-get install clamav &&
    apt-get install clamav-daemon &&
    apt-get install clamav-freshclam
   ``` 

   4.2 To update the database, we stop the `clamav-freshclam` service and then run the corresponding command as follows:
    ``` bash
    /etc/init.d/clamav-freshclam stop && freshclam
    ```
   4.3 Config the ClamAv service:
   * 4.3.1 run the clamAv with TCP|IP protocol.   
     -- Under the file `/etc/clamav/clamd.conf` Add these lines to the config:

   ```
   TCPSocket {{port}} (3310 often we choose this one)
   TCPAddr {{server ip}} (127.0.0.1 if the clamav service is installed locally)
    ```
   -- Under the file `/etc/systemd/system/clamav-daemon.service.d/extend.conf` Add the line:
    ```
    ListenStream = {{ip: port}} (127.0.0.1:3310 in our example)
    ```
   * 4.3.2 or run the clamAv with UNIX socket protocol.  
     -- Under the file /etc/clamav/clamd.conf Add the line :
   ```
    LocalSocket: /var/run/clamav/clamd.sock
    ```

   * 4.3.3 After these configs you can restart your service :
    ``` bash
    $ systemctl daemon-reload && /etc/init.d/clamav-daemon restart
    ```
   * 4.3.4 Add the `clamav` user to the group `www-data`:
    ``` bash
      $ usermod -a -G www-data clamav && /etc/init.d/clamav-daemon restart
    ```
   * To make sure, execute the command :
   ```bash
    $ groups clamav
   ```
   * Output :
   ```
     clamav : clamav www-data
   ```


* To make sure that our clamav-daemon service is running under the socket ip:port configured  or the unix local socket , we execute the command :
``` bash
netstat -lpn | grep clam
```
* Output if the clamAv is lessening to the configured unix local socket :
``` bash 
unix  2      [ ACC ]     STREAM     LISTENING     239406   1121/clamd           /var/run/clamav/clamd.sock
```
* Output if the clamAv is lessening to the configured TCP|IP socket :
``` bash
TCP   0         0 127.0.0.1:3310            0.0.0.0.*           LISTEN          97/clamd
```
* Output if we configured both protocols:
``` bash
unix  2      [ ACC ]     STREAM     LISTENING     239406   1121/clamd           /var/run/clamav/clamd.sock
TCP   0         0 127.0.0.1:3310            0.0.0.0.*           LISTEN          97/clamd
```


5. Configuration of app/config/config.yml file

-
```yaml
masilia_clamav:
    #required config , 'unix:///path/to/clamav/unix_local_socket' if unix socket or 'tcp://ip:port' if tcp|ip socket>>
    socket_path: 'unix:///path/to/clamav/unix_local_socket|tcp://ip:port'
    #optional config, if you have chroot partition in your server you can the path 
    root_path: '/opt/www/project-root' ('' as default)
    #optional config, you can add it if your form is handled with ezForm Builder 
    ezform_builder_enabled: true|false (false as default)
    #optional config, if we want to scan on streaming the uploaded file
    # if the clamAv is running in the remote server , we must use the the TCP|IP protocol and enable the stream scan
    # when there issue with the scan and you get in logs the message like ('reason' => 'lstat()'), this option may resolve your issue
    enable_stream_scan: true|false (false as default value)
    #To enable validation on BO with ezbinaryfile FieldType
    enable_binary_field_type_validator: true|false (false as default value)
   
```


Usage
------------

5.1 Antivirus constraint

- In symfony object form
  To enable Antivirus constraint on your form Type for the input file add the following lines .

    ```php
    <?php
    
    use Masilia\ClamavBundle\Constraints\Antivirus;
    use Symfony\Component\Form\Extension\Core\Type\FileType;
    use Symfony\Component\Form\FormBuilder;
    
    public function buildForm(FormBuilder $builder, array $options)
    {
        // ...
        $builder->add('file',FileType::class,[
                        'constraints'=>[new Antivirus()]
                    ]);
        // ...
    }
    
    ```
- In eZ Platform form builder you can just pass the parameter ```ezform_builder_enabled``` to ``true`` then add validation with Antivirus in the config file panel on the BO while editing your form.
- In BO to active scan with ezbinaryfile FiledType you can just pass the parameter ```enable_binary_field_type_validator``` to ``true``

5.4 Customize error message
* To customize server error message add this message error key "antivirus_constraint_message" in messages translation file Resources/translations/messages.{locale}.{yml|php|xliff}
6. Override the antivirus service
   *To do, you implement the interface ``Masilia\ClamavBundle\Services\AntivirusInterface`` and the value to return from your service is an array with keys `[status => Ok|KO]` and `[reason => ' your statment ... ']`
* then declare your service as following:
```Yaml
services:
   Masilia\ClamavBundle\Services\AntiVirusInterface: '@your.antivirus.service_alias'
```

Troubleshooting
-------------
if you get in your logs the message like :
```
'status' => 'KO',   'reason' => 'file not scanned :Socket operation failed: No such file or directory (SOCKET_ENOENT)',   'originalFilename' => 'Test (4).pdf', )
```
The connexion with the antivirus server is failed, make sure that is the correct socket root path `socket_root`
```
 'id' => '2',   'filename' => '/tmp/phpZa1j3O',   'reason' => 'lstat()',   'status' => 'failed',   'originalFilename' => 'Test (4).pdf', )
```
There is an issue with file path ,first check the `root_path` param , if the issue steel occurring in that case the stream scan is privileged , you can enable it in parameters, it may resolve yur issue.
```
 'status' => 'KO',   'reason' => 'file not scanned :Warning: file_get_contents(/var/www//tmp/php9w4fcr): failed to open stream: No such file or directory',   'originalFilename' => 'Test (4).pdf', )
```
Check the param `root_path` probably you've put the wrong path where the file is uploaded by PHP 