Add-Type -Path 'C:\Windows\Microsoft.NET\Framework64\v4.0.30319\System.Windows.Forms.dll'
$position = New-Object System.Drawing.Point($x, $y);
[System.Windows.Forms.Cursor]::Position = $position;