<?php

namespace ZK;

use ZKLib;

class Attendance
{
    /**
     * @param ZKLib $self
     * @return array [uid, id, state, timestamp]
     */
    public function get(ZKLib $self,$date)
    {
        $self->_section = __METHOD__;

        $command = Util::CMD_ATT_LOG_RRQ;
        $command_string = '';

        $session = $self->_command($command, $command_string, Util::COMMAND_TYPE_DATA);
        if ($session === false) {
            return [];
        }

        $attData = Util::recData($self);

        $attendance = [];
        if (!empty($attData)) {
            $attData = substr($attData, 10);

            while (strlen($attData) > 40) {
                $u = unpack('H78', substr($attData, 0, 39));

                $u1 = hexdec(substr($u[1], 4, 2));
                $u2 = hexdec(substr($u[1], 6, 2));
                $uid = $u1 + ($u2 * 256);
                $id = hex2bin(substr($u[1], 8, 18));
                $id = str_replace(chr(0), '', $id);
                $state = hexdec(substr($u[1], 56, 2));
                $timestamp = Util::decodeTime(hexdec(Util::reverseHex(substr($u[1], 58, 8))));
                $type = hexdec(Util::reverseHex(substr($u[1], 66, 2 )));
                
				if (($timestamp >= "$date 01:00:00") && ($timestamp <= "$date 23:59:00")) {
                    // if (str_contains($timestamp, $date)) { 

                    $attendance[] = [
                        'uid' => $uid,
                        'id' => $id,
                        'state' => $state,
                        'timestamp' => $timestamp,
                        'type' => $type
                    ];
                }
                

                $attData = substr($attData, 40);
            }

        }

        return $attendance;
    }

    /**
     * @param ZKLib $self
     * @return array [uid, id, state, timestamp]
     */
    public function get_date(ZKLib $self,$date1,$date2)
    {
        $self->_section = __METHOD__;

        $command = Util::CMD_ATT_LOG_RRQ;
        $command_string = '';

        $session = $self->_command($command, $command_string, Util::COMMAND_TYPE_DATA);
        if ($session === false) {
            return [];
        }

        $attData = Util::recData($self);

        $attendance = [];
        if (!empty($attData)) {
            $attData = substr($attData, 10);

            while (strlen($attData) > 40) {
                $u = unpack('H78', substr($attData, 0, 39));

                $u1 = hexdec(substr($u[1], 4, 2));
                $u2 = hexdec(substr($u[1], 6, 2));
                $uid = $u1 + ($u2 * 256);
                $id = hex2bin(substr($u[1], 8, 18));
                $id = str_replace(chr(0), '', $id);
                $state = hexdec(substr($u[1], 56, 2));
                $timestamp = Util::decodeTime(hexdec(Util::reverseHex(substr($u[1], 58, 8))));
                $type = hexdec(Util::reverseHex(substr($u[1], 66, 2 )));

				if ($timestamp >= "$date1 01:00:00" && $timestamp <= "$date2 23:59:00") {
                    $attendance[] = [
                        'uid' => $uid,
                        'id' => $id,
                        'state' => $state,
                        'timestamp' => $timestamp,
                        'type' => $type
                    ];
                }
                

                $attData = substr($attData, 40);
            }

        }

        return $attendance;
    }
    /**
     * @param ZKLib $self
     * @return array [uid, id, state, timestamp]
     */
    public function get_all(ZKLib $self)
    {
        $self->_section = __METHOD__;

        $command = Util::CMD_ATT_LOG_RRQ;
        $command_string = '';

        $session = $self->_command($command, $command_string, Util::COMMAND_TYPE_DATA);
        if ($session === false) {
            return [];
        }

        $attData = Util::recData($self);

        $attendance = [];
        if (!empty($attData)) {
            $attData = substr($attData, 10);

            while (strlen($attData) > 40) {
                $u = unpack('H78', substr($attData, 0, 39));

                $u1 = hexdec(substr($u[1], 4, 2));
                $u2 = hexdec(substr($u[1], 6, 2));
                $uid = $u1 + ($u2 * 256);
                $id = hex2bin(substr($u[1], 8, 18));
                $id = str_replace(chr(0), '', $id);
                $state = hexdec(substr($u[1], 56, 2));
                $timestamp = Util::decodeTime(hexdec(Util::reverseHex(substr($u[1], 58, 8))));
                $type = hexdec(Util::reverseHex(substr($u[1], 66, 2 )));

                    $attendance[] = [
                        'uid' => $uid,
                        'id' => $id,
                        'state' => $state,
                        'timestamp' => $timestamp,
                        'type' => $type
                    ];
                

                $attData = substr($attData, 40);
            }

        }

        return $attendance;
    }

    /**
     * @param ZKLib $self
     * @return bool|mixed
     */
    public function clear(ZKLib $self)
    {
        $self->_section = __METHOD__;

        $command = Util::CMD_CLEAR_ATT_LOG;
        $command_string = '';

        return $self->_command($command, $command_string);
    }
}
