<?php

// Define the directory containing your projects
$hostDir = 'F:\\server\\www';

// Define the location of the httpd-vhosts.conf file
$vhostsConf = 'F:\\server\\apache\\conf\\extra\\httpd-vhosts.conf';

// Define the location of the hosts file
$hostsFile = 'C:\\Windows\\System32\\drivers\\etc\\hosts';

// Define the directory for SSL certificates
$sslDir = 'F:\\server\\apache\\conf\\ssl';

// Path to OpenSSL binary
$opensslPath = 'F:\\server\\apache\\bin\\openssl.exe';

// Path to OpenSSL configuration file
$opensslConfig = 'F:\\server\\apache\\conf\\openssl.cnf';

// Create the SSL directory if it doesn't exist
if (!file_exists($sslDir)) {
    mkdir($sslDir, 0777, true);
}

// Start writing to the httpd-vhosts.conf file
file_put_contents($vhostsConf, "# Auto-generated Virtual Hosts\n");

// Read the hosts file content
$hostsContent = file($hostsFile);

# generate random string
function randStr($minLength = 5, $maxLength = 10) {
    return substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', $maxLength)), 0, rand($minLength, $maxLength));
}

// Filter out the domains from the hosts file and generate virtual hosts
foreach ($hostsContent as $line) {
    // Skip empty lines or comments
    if (empty(trim($line)) || strpos(trim($line), '#') === 0) {
        continue;
    }

    // Extract the domain name (assuming the format "127.0.0.1 www.project1.com")
    if (preg_match('/127\.0\.0\.1\s+(www\.[a-zA-Z0-9-]+\.com)/', $line, $matches)) {
        $domain = $matches[1];
        $projectName = substr($domain, 4, -4); // Extract project name (strip "www." and ".com")

        // Check if the corresponding project folder exists
        if (is_dir($hostDir . DIRECTORY_SEPARATOR . $projectName)) {
            echo "Creating virtual host for $domain\n";
			
			$orgName = randStr();
			$unitName = randStr();
			$location = randStr();
			
            // Create SSL certificate and key if they don't exist
            $certFile = "$sslDir\\$domain.crt";
            $keyFile = "$sslDir\\$domain.key";
            if (!file_exists($certFile) || !file_exists($keyFile)) {
                echo "Generating SSL certificate for $domain\n";
                $command = "\"$opensslPath\" req -x509 -nodes -days 365 -newkey rsa:2048 -keyout \"$keyFile\" -out \"$certFile\" -subj \"/C=US/ST=$orgName/L=$location/O=$orgName/OU=$unitName/CN=$domain\" -config \"$opensslConfig\"";
                exec($command);
            } else {
                echo "SSL certificate already exists for $domain, skipping...\n";
            }
			# Redirect permanent / http://$domain
            // Create a new Virtual Host entry
            $vhostEntry = <<<EOT
<VirtualHost *:80>
    DocumentRoot "$hostDir\\$projectName"
    ServerName $domain
    <Directory "$hostDir\\$projectName">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

<VirtualHost *:443>
    DocumentRoot "$hostDir\\$projectName"
    ServerName $domain
    SSLEngine on
    SSLCertificateFile "$sslDir\\$domain.crt"
    SSLCertificateKeyFile "$sslDir\\$domain.key"
    <Directory "$hostDir\\$projectName">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

EOT;
            file_put_contents($vhostsConf, $vhostEntry, FILE_APPEND);
        } else {
            echo "Project folder not found for domain $domain\n";
        }
    }
}

echo "Virtual hosts and SSL certificates generated based on the hosts file.\n";
