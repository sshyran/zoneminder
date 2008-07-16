<?php

header("Content-type: text/plain" );

$response = array(
    'result' => 'Ok',
);

if ( empty($_REQUEST['id']) )
{
    $response['result'] = 'Error';
    $response['message'] = 'No event id(s) supplied';
}

if ( $response['result'] != 'Error' )
{
    $refreshEvent = false;
    $refreshParent = false;

    if ( canEdit( 'Events' ) )
    {
        $response['result'] = 'Ok';
        switch ( $_REQUEST['action'] )
        {
            case "rename" :
            {
                if ( !empty($_REQUEST['eventName']) )
                {
                    dbQuery( "update Events set Name = '".dbEscape($_REQUEST['eventName'])."' where Id = '".dbEscape($_REQUEST['id'])."'" );
                }
                else
                {
                    $response['result'] = 'Error';
                    $response['message'] = 'No new event name supplied';
                }
                break;
            }
            case "eventdetail" :
            {
                dbQuery( "update Events set Cause = '".dbEscape($_REQUEST['newEvent']['Cause'])."', Notes = '".dbEscape($_REQUEST['newEvent']['Notes'])."' where Id = '".dbEscape($_REQUEST['id'])."'" );
                $refreshEvent = true;
                $refreshParent = true;
                break;
            }
            case "archive" :
            case "unarchive" :
            {
                $archiveVal = ($_REQUEST['action'] == "archive")?1:0;
                dbQuery( "update Events set Archived = ".$archiveVal." where Id = '".dbEscape($_REQUEST['id'])."'" );
                $refreshEvent = true;
                break;
            }
            case "delete" :
            {
                deleteEvent( dbEscape($_REQUEST['id']) );
                break;
            }
            default :
            {
                $response['result'] = 'Error';
                break;
            }
        }
    }
    if ( canView( 'Events' ) )
    {
        $response['result'] = 'Ok';
        switch ( $_REQUEST['action'] )
        {
            case "video" :
            {
                if ( empty($_REQUEST['videoFormat']) )
                {
                    $response['result'] = 'Error';
                    $response['message'] = "Video Generation Failure, no format given";
                }
                elseif ( empty($_REQUEST['rate']) )
                {
                    $response['result'] = 'Error';
                    $response['message'] = "Video Generation Failure, no rate given";
                }
                elseif ( empty($_REQUEST['scale']) )
                {
                    $response['result'] = 'Error';
                    $response['message'] = "Video Generation Failure, no scale given";
                }
                else
                {
                    $sql = "select E.*,M.Name as MonitorName,M.DefaultRate,M.DefaultScale from Events as E inner join Monitors as M on E.MonitorId = M.Id where E.Id = ".dbEscape($_REQUEST['id']).monitorLimitSql();
                    if ( !($event = dbFetchOne( $sql )) )
                    {
                        $response['result'] = 'Error';
                        $response['message'] = "Video Generation Failure, can't load event";
                    }
                    else
                    {
                        if ( $videoFile = createVideo( $event, $_REQUEST['videoFormat'], $_REQUEST['rate'], $_REQUEST['scale'], !empty($_REQUEST['overwrite']) ) )
                        {
                            //$eventPath = getEventPath( $event );
                            //$response['videoPath'] = $eventPath.'/'.$videoFile;
                            $response['videoPath'] = $videoFile;
                        }
                        else
                        {
                            $response['result'] = 'Error';
                            $response['message'] = "Video Generation Failed";
                        }
                    }
                }
                break;
            }
            case 'deleteVideo' :
            {
                unlink( $videoFiles[$_REQUEST['id']] );
                unset( $videoFiles[$_REQUEST['id']] );
                break;
            }
            case "export" :
            {
                require_once( 'zm_export_funcs.php' );

                if ( !empty($_REQUEST['exportDetail']) )
                    $exportDetail = $_SESSION['export']['detail'] = $_REQUEST['exportDetail'];
                else
                    $exportDetail = false;
                if ( !empty($_REQUEST['exportFrames']) )
                    $exportFrames = $_SESSION['export']['frames'] = $_REQUEST['exportFrames'];
                else
                    $exportFrames = false;
                if ( !empty($_REQUEST['exportImages']) )
                    $exportImages = $_SESSION['export']['images'] = $_REQUEST['exportImages'];
                else
                    $exportImages = false;
                if ( !empty($_REQUEST['exportVideo']) )
                    $exportVideo = $_SESSION['export']['video'] = $_REQUEST['exportVideo'];
                else
                    $exportVideo = false;
                if ( !empty($_REQUEST['exportMisc']) )
                    $exportMisc = $_SESSION['export']['misc'] = $_REQUEST['exportMisc'];
                else
                    $exportMisc = false;
                if ( !empty($_REQUEST['exportFormat']) )
                    $exportFormat = $_SESSION['export']['format'] = $_REQUEST['exportFormat'];
                else
                    $exportFormat = '';

                if ( $exportFile = exportEvents( $_REQUEST['id'], $exportDetail, $exportFrames, $exportImages, $exportVideo, $exportMisc, $exportFormat ) )
                {
                    $response['exportFile'] = $exportFile;
                }
                else
                {
                    $response['result'] = 'Error';
                    $response['message'] = 'Export Failed';
                }
                break;
            }
            default :
            {
                $response['result'] = 'Error';
                break;
            }
        }
    }
    if ( $response['result'] == 'Ok' )
    {
        $response['refreshParent'] = $refreshParent;
        $response['refreshEvent'] = $refreshEvent;
    }
    elseif ( !$response['message'] )
        $response['message'] = 'Unrecognised action or insufficient permissions';
}


echo jsValue( $response );

?>