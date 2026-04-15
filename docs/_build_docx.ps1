$ErrorActionPreference = 'Stop'
$root = 'C:\xampp\htdocs\WebResort_FinalProj_Revised'
$md = Join-Path $root 'docs\9Waves-System-Documentation.md'
$outDocx = Join-Path $root 'docs\9Waves-System-Documentation.docx'
$tmp = Join-Path $root 'docs\_docx_tmp'
$zip = Join-Path $root 'docs\_docx_tmp.zip'
$utf8 = New-Object System.Text.UTF8Encoding($false)

function Write-Utf8File([string]$Path, [string]$Content) {
  [System.IO.File]::WriteAllText($Path, $Content, $utf8)
}

if (Test-Path $tmp) { Remove-Item -Recurse -Force $tmp }
if (Test-Path $zip) { Remove-Item -Force $zip }
if (Test-Path $outDocx) { Remove-Item -Force $outDocx }

New-Item -ItemType Directory -Path $tmp | Out-Null
New-Item -ItemType Directory -Path (Join-Path $tmp '_rels') | Out-Null
New-Item -ItemType Directory -Path (Join-Path $tmp 'docProps') | Out-Null
New-Item -ItemType Directory -Path (Join-Path $tmp 'word') | Out-Null

$ct = @'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
  <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
  <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>
'@
Write-Utf8File (Join-Path $tmp '[Content_Types].xml') $ct

$rels = @'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>
'@
Write-Utf8File (Join-Path $tmp '_rels\.rels') $rels

$now = [DateTime]::UtcNow.ToString('s') + 'Z'
$core = @'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <dc:title>9 Waves System Documentation</dc:title>
  <dc:creator>GitHub Copilot</dc:creator>
  <cp:lastModifiedBy>GitHub Copilot</cp:lastModifiedBy>
  <dcterms:created xsi:type="dcterms:W3CDTF">{0}</dcterms:created>
  <dcterms:modified xsi:type="dcterms:W3CDTF">{1}</dcterms:modified>
</cp:coreProperties>
'@ -f $now, $now
Write-Utf8File (Join-Path $tmp 'docProps\core.xml') $core

$app = @'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
  <Application>Microsoft Office Word</Application>
</Properties>
'@
Write-Utf8File (Join-Path $tmp 'docProps\app.xml') $app

$lines = Get-Content -Path $md
$sb = New-Object System.Text.StringBuilder
[void]$sb.Append('<?xml version="1.0" encoding="UTF-8" standalone="yes"?>')
[void]$sb.Append('<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>')
foreach ($line in $lines) {
  $esc = [System.Security.SecurityElement]::Escape($line)
  if ([string]::IsNullOrEmpty($esc)) {
    [void]$sb.Append('<w:p/>')
  } else {
    [void]$sb.Append('<w:p><w:r><w:t xml:space="preserve">' + $esc + '</w:t></w:r></w:p>')
  }
}
[void]$sb.Append('<w:sectPr><w:pgSz w:w="12240" w:h="15840"/><w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="708" w:footer="708" w:gutter="0"/></w:sectPr>')
[void]$sb.Append('</w:body></w:document>')
Write-Utf8File (Join-Path $tmp 'word\document.xml') $sb.ToString()

Compress-Archive -Path (Join-Path $tmp '*') -DestinationPath $zip -Force
Move-Item -Path $zip -Destination $outDocx
Remove-Item -Recurse -Force $tmp
Write-Output ('CREATED: ' + $outDocx)
