<?php

declare(strict_types=1);

namespace TXC\NUT\Telnet;

enum Command: int implements CharacterInterface
{
    // Telnet protocol characters (don't change)
    case THENULL = 0x00;

    // Subnegotiation End
    case SE = 0xF0;
    // No Operation
    case NOP = 0xF1;
    // Data Mark.
    // Indicates the position of a Synch event within the data stream.
    // This should always be accompanied by a TCP urgent notification.
    case DM = 0xF2;
    // Break. Indicates that the "break" or "attention" key was hit.
    case BRK = 0xF3;
    // Suspend, interrupt or abort the process to which the NVT is connected.
    case IP = 0xF4;
    // Abort output. Allows the current process Abort output.
    // Allows the current process to run to completion but do not send
    // its output to the user.
    case AO = 0xF5;
    // Are You There? Send back to the NVT some
    // visible evidence that the AYT was received.
    case AYT = 0xF6;
    // Erase character. The receiver should delete
    // the last preceding undeleted
    // character from the data stream.
    case EC = 0xF7;
    // Erase line. Delete characters from the data
    // stream back to but not including the previous CRLF.
    case EL = 0xF8;
    // Go Ahead. Used, under certain circumstances,
    // to tell the other end that it can transmit.
    case GA = 0xF9;
    // Subnegotiation of the indicated option follows.
    case SB = 0xFA;
    // Indicates the desire to begin
    // performing, or confirmation that you are
    // now performing, the indicated option.
    case WILL = 0xFB;
    // Indicates the refusal to perform, or
    // continue performing, the indicated option.
    case WONT = 0xFC;
    // Indicates the request that the other
    // party perform, or confirmation that you are
    // expecting the other party to
    // perform, the indicated option.
    case DO = 0xFD;
    // Indicates the demand that the other
    // party stop performing, or confirmation that you
    // are no longer expecting the other party to
    // perform, the indicated option.
    case DONT = 0xFE;
    // Interpret As Command
    case IAC = 0xFF;

    public function chr(): string
    {
        return chr($this->value);
    }
}
