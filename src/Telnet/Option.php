<?php

declare(strict_types=1);

namespace TXC\NUT\Telnet;

enum Option: int implements CharacterInterface
{
    /**
     * Telnet protocol options code (don't change)
     * These ones all come from arpa/telnet.h
     */
    // 8-bit data path
    case BINARY = 0x00;
    // echo
    case ECHO = 0x01;
    // prepare to reconnect
    case RCP = 0x02;
    // suppress go ahead
    case SGA = 0x03;
    // approximate message size
    case NAMS = 0x04;
    // give status
    case STATUS = 0x05;
    // timing mark
    case TM = 0x06;
    // remote controlled transmission and echo
    case RCTE = 0x07;
    // negotiate about output line width
    case NAOL = 0x08;
    // negotiate about output page size
    case NAOP = 0x09;
    // negotiate about CR disposition
    case NAOCRD = 0x0A;
    // negotiate about horizontal tabstops
    case NAOHTS = 0x0B;
    // negotiate about horizontal tab disposition
    case NAOHTD = 0x0C;
    // negotiate about formfeed disposition
    case NAOFFD = 0x0D;
    // negotiate about vertical tab stops
    case NAOVTS = 0x0E;
    // negotiate about vertical tab disposition
    case NAOVTD = 0x0F;
    // negotiate about output LF disposition
    case NAOLFD = 0x10;
    // extended ascii character set
    case XASCII = 0x11;
    // force logout
    case LOGOUT = 0x12;
    // byte macro
    case BM = 0x13;
    // data entry terminal
    case DET = 0x14;
    // supdup protocol
    case SUPDUP = 0x15;
    // supdup output
    case SUPDUPOUTPUT = 0x16;
    // send location
    case SNDLOC = 0x17;
    // terminal type
    case TTYPE = 0x18;
    // end or record
    case EOR = 0x19;
    // TACACS user identification
    case TUID = 0x1A;
    // output marking
    case OUTMRK = 0x1B;
    // terminal location number
    case TTYLOC = 0x1C;
    // 3270 regime
    case VT3270REGIME = 0x1D;
    // X.3 PAD
    case X3PAD = 0x1E;
    // window size
    case NAWS = 0x1F;
    // terminal speed
    case TSPEED = 0x20;
    // remote flow control
    case LFLOW = 0x21;
    // Linemode option
    case LINEMODE = 0x22;
    // X Display Location
    case XDISPLOC = 0x23;
    // Old - Environment variables
    case OLD_ENVIRON = 0x24;
    // Authenticate
    case AUTHENTICATION = 0x25;
    // Encryption option
    case ENCRYPT = 0x26;
    // New - Environment variables
    case NEW_ENVIRON = 0x27;

    /**
     * the following ones come from
     * http://www.iana.org/assignments/telnet-options
     * Unfortunately, that document does not assign identifiers
     * to all of them, so we are making them up
     */
    // TN3270E
    case TN3270E = 0x28;
    // XAUTH
    case XAUTH = 0x29;
    // CHARSET
    case CHARSET = 0x2A;
    // Telnet Remote Serial Port
    case RSP = 0x2B;
    // Com Port Control Option
    case COM_PORT_OPTION = 0x2C;
    // Telnet Suppress Local Echo
    case SUPPRESS_LOCAL_ECHO = 0x2D;
    // Telnet Start TLS
    case TLS = 0x2E;
    // KERMIT
    case KERMIT = 0x2F;
    // SEND-URL
    case SEND_URL = 0x30;
    // FORWARD_X
    case FORWARD_X = 0x31;
    // TELOPT PRAGMA LOGON
    case PRAGMA_LOGON = 0x8A;
    // TELOPT SSPI LOGON
    case SSPI_LOGON = 0x8B;
    // TELOPT PRAGMA HEARTBEAT
    case PRAGMA_HEARTBEAT = 0x8C;
    // Extended-Options-List
    case EXOPL = 0xFF;

    public function chr(): string
    {
        return chr($this->value);
    }
}
