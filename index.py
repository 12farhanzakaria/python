import hashlib
import os
import requests
currentPlaylist = requests.get('https://acefile.my.id/server6/11/76ccceb6b05fa1ab57d3811bd056cfde/v_720p/index-v1-a1.m3u8')
currentFileHash = hashlib.md5()
for currentFileHashGenerate in currentPlaylist.iter_content(8192):
 currentFileHash.update(currentFileHashGenerate)
folderPlaylist = 'drive/MyDrive/Upload/HLS/' + currentFileHash.hexdigest() + '.txt'
indexPlaylist = currentPlaylist.text.splitlines()
if (os.path.isfile(folderPlaylist)) :
 indexPlaylist = indexPlaylist[len(open(folderPlaylist, 'r').read().strip().splitlines()):]
else :
open(folderPlaylist, 'w+').close()
for iteration in indexPlaylist:
if 'https' in iteration:
  currentSegment = iteration
elif iteration.endswith('.ts'):
  currentSegment = os.path.dirname(currentPlaylist.url) + '/' + iteration
else:
  currentSegment = None
if currentSegment:
  currentFile = requests.get(currentSegment)
  if int(currentFile.headers['content-length']) <= 5242880:
   uploadFile = requests.post('https://telegra.ph/upload', files={'file': ('file', b'\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00', 'image/png')}).json()
   if type(uploadFile) is list and 'src' in uploadFile[0]:
    iteration = os.path.splitext(os.path.basename(uploadFile[0]['src']))[0]
open(folderPlaylist, 'a').write('\n' + iteration)
