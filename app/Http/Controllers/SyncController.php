<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use GuzzleHttp\Client;


class SyncController extends Controller
{
    public function index()
    {
        $googleDisk = \Storage::disk('google');
        $files = $googleDisk->allFiles();
    }


    public function getDropboxTopFolder()
    {
        $dropboxClient = new Client([
            'base_uri' => 'https://api.dropboxapi.com/2/',
            'headers' => [
                'Authorization' => "Bearer " . config('dropbox.accessToken'),
                'Content-Type' => 'application/json',
            ],
        ]);

        $response = $dropboxClient->post('files/list_folder', [
            'json' => [
                'path' => '',
                'recursive' => false,
                'include_media_info' => false,
                'include_deleted' => false,
                'include_has_explicit_shared_members' => false,
            ],
        ]);

        $body = json_decode($response->getBody(), true);
        $files = $body['entries'];

        $path = isset($files[0]) ? isset($files[0]['path_display']) ? $files[0]['path_display'] : '' : '';
        return $path;
    }

    public function syncGoogleFolders()
    {
        $googleDriveFolder = $this->getGoogleDriveTopFolder();
        $dropboxFolder = $this->getDropboxTopFolder();

        $this->syncFilesDropBox($dropboxFolder, $googleDriveFolder);
       return redirect()->back()->with('message', "Google Drive has been sync");
    }

     public function syncDropBoxFolders()
    {

        $googleDriveFolder = $this->getGoogleDriveTopFolder();
        $dropboxFolder = $this->getDropboxTopFolder();

        $this->syncFilesGoogleDrive($googleDriveFolder, $dropboxFolder);
        return redirect()->back()->with('message', "Drop box has been sync");
    }

    private function getGoogleDriveTopFolder()
    {
        $drive = Storage::disk('google');
        $files = $drive->allFiles();

        $path = isset($files[0]) ? $files[0] : '';
        return $path;
    }


    private function syncFilesGoogleDrive($sourceFolder, $destinationFolder)
    {

        $sourceFiles = Storage::disk('google')->allFiles('');

        foreach ($sourceFiles as $sourceFile) {
            $destinationFile = str_replace($sourceFolder, $destinationFolder, $sourceFile);


            if (!Storage::disk('dropbox')->exists($destinationFile)) {
                $fileContents = Storage::disk('google')->get($sourceFile);
                Storage::disk('dropbox')->put($destinationFile, $fileContents);
                }
        }

        $destinationFiles = Storage::disk('dropbox')->allFiles('');

        foreach ($destinationFiles as $destinationFile) {
            $sourceFile = str_replace($destinationFolder, $sourceFolder, $destinationFile);


            if (!Storage::disk('google')->exists($sourceFile)) {

                Storage::disk('dropbox')->delete($destinationFile);
            } else {
                $sourceContents = Storage::disk('google')->get($sourceFile);
                $destinationContents = Storage::disk('dropbox')->get($destinationFile);

                if ($sourceContents !== $destinationContents) {
                    if(!$sourceContents){
                        continue;
                    }
                    Storage::disk('dropbox')->put($destinationFile, $sourceContents);
                }
            }
        }


    }
    private function syncFilesDropBox($sourceFolder, $destinationFolder)
    {
        $sourceFiles = Storage::disk('dropbox')->allFiles('');
        
        foreach ($sourceFiles as $sourceFile) {

            $destinationFile = str_replace($sourceFolder, $destinationFolder, $sourceFile);


            if (!Storage::disk('google')->exists($destinationFile)) {

                $fileContents = Storage::disk('dropbox')->get($sourceFile);
                Storage::disk('google')->put($destinationFile, $fileContents);
            }
        }

        $destinationFiles = Storage::disk('google')->allFiles('');

        foreach ($destinationFiles as $destinationFile) {

            $sourceFile = str_replace($destinationFolder, $sourceFolder, $destinationFile);


            if (!Storage::disk('dropbox')->exists($sourceFile)) {
                Storage::disk('google')->delete($destinationFile);
            } else {


                $sourceContents = Storage::disk('dropbox')->get($sourceFile);
                $destinationContents = Storage::disk('google')->get($destinationFile);

                if ($sourceContents !== $destinationContents) {


                    if(!$sourceContents){
                        continue;
                    }
                    Storage::disk('google')->put($destinationFile, $sourceContents);
                }
            }
        }


    }
}
