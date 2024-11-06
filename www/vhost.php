<?php

// Define the directory containing your projects
$hostDir = 'F:\\server\\www';

// Define the directory containing your projects
$sitesEnabled = 'F:\\server\\config\\sites-enabled';

// Define the location of the httpd-vhosts.conf file
$vhostsConf = 'F:\\server\\apache\\conf\\extra\\httpd-vhosts.conf';

// Define the location of the hosts file
$hostsFile = 'C:\\Windows\\System32\\drivers\\etc\\hosts';

// Backup the existing hosts file
copy($hostsFile, $hostsFile . '.bak');

// Start writing to the httpd-vhosts.conf file
file_put_contents($vhostsConf, "# Auto-generated Virtual Hosts\n");

// Get current project folders
$folders = scandir($hostDir); # scandir scarn under the folder and return array like this [ 0=>folder_name or file_name]
$dir_separator = $hostDir . DIRECTORY_SEPARATOR;
$folders = array_filter($folders, function ($folder) use ($dir_separator) {
	return is_dir($dir_separator . $folder) && !in_array($folder, ['.', '..']); # is_dir check if $folders is a folder or file if it folder return array of foldes [ 0=>folder_name]
});

/*
*	Add or update Virtual Hosts and hosts entries
*	Looping over the $folders
*/
foreach ($folders as $projectName) {
	$domain = strtolower("www.$projectName.test"); # Convert the domain to lowercase

	$hostsContent = file_get_contents($hostsFile); # Check if the domain already exists in the hosts file

	$is_public = $dir_separator . $projectName . DIRECTORY_SEPARATOR . 'public'; # make public directory for feather check is project hava a public folder

	if (strpos($hostsContent, $domain) === false) { # check domain exit or not 
		echo "Run ........  $domain    Craeate Successfull  ......  Done!\n";

		$vhostEntry = '';
		# check if exists public and exist index.php in project/public/index.php and if no exist project/index.php make virtual Host
		if (is_dir($is_public) && in_array('index.php', scandir($is_public)) && !in_array('index.php', scandir($dir_separator . $projectName))) {

			$vhostEntry = <<<EOT
<VirtualHost *:80>
	DocumentRoot "$hostDir\\$projectName\\public"
	ServerName $domain
	<Directory "$hostDir\\$projectName\\public">
		Options Indexes FollowSymLinks
		AllowOverride All
		Require all granted
	</Directory>
</VirtualHost>

EOT;
		} else {
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

EOT;
		}

		$filePath = $sitesEnabled . DIRECTORY_SEPARATOR . 'auto.' . $projectName . '.conf';  # Create Virtual Hosts 

		file_put_contents($filePath, $vhostEntry); # generated Virtual Host entry for each each IP/Domain

		//file_put_contents($vhostsConf, $vhostEntry, FILE_APPEND); # Create a new Virtual Host entry

		file_put_contents($hostsFile, "127.0.0.1      $domain\n", FILE_APPEND); # Add IP and doemain to the hosts file
	} else {
		echo strpos($hostsContent, $domain) !== false ? "Run ........  $domain    Exists  ......  Done!\n" : "Run ........  Errr   Somethings wrong  ......  Fail\n";
	}
}

// Remove stale domains from the hosts file
echo "Run ........  Non Existing IP/Domain    Removing  ......  Done!\n";
$updatedHostsContent = [];
$lines = file($hostsFile);
foreach ($lines as $line) {

	if (preg_match('/^127\.0\.0\.1\s+www\./', $line, $matches)) {
		$domain = trim(str_replace('127.0.0.1', '', $line));
		$projectName = substr($domain, 4, -5); // Strip "www." and ".com

		#if phpmyadmin IP/Domain exist in hosts file ignore for removed form the hosts IP/Domain list 
		if ('phpmyadmin' !== $projectName) {
			// Check if the project folder still exists
			if (!is_dir($dir_separator . $projectName)) {
				echo "Run ........  $domain    Removed  ......  Done!\n";
				continue;
			}
		}
	}
	$updatedHostsContent[] = $line; # store only exist project IP/Domain
}

// Write the updated hosts content back to the file
file_put_contents($hostsFile, implode('', $updatedHostsContent));

# run server restart command
$command = 'net stop apache && net start apache';  # command
exec($command, $output, $return_var); # executed command an external commagd

# server restart prossessing message printing
foreach ($output as $message) {
	echo strlen(trim($message)) > 0 ? "Run ........  $message  ......  Done!\n" : FALSE;
}

# server sestart status code message set
echo $return_var === 0 ?
	"Run ........  Server restart successfully  ......  Done!\n" :
	"Run ........  Failed to restart Apache server  .......  Fail!\n";

echo "Run ........  Prossesing  ......  Done!\n";
