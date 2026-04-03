let BTHOME_V2_SVC_ID_STR = "fcd2";
let BTHOME_V1_SVC_ID_STR = "181c";

let SCAN_DURATION = BLE.Scanner.INFINITE_SCAN;
let ACTIVE_SCAN = false;

let uint8 = 0;
let int8 = 1;
let uint16 = 2;
let int16 = 3;
let uint24 = 4;
let int24 = 5;

function getByteSize(type) {
  if (type === uint8 || type === int8) return 1;
  if (type === uint16 || type === int16) return 2;
  if (type === uint24 || type === int24) return 3;
  //impossible as advertisements are much smaller;
  return 255;
}

let BTH = [];
BTH[0x00] = { n: "pid", t: uint8 };
BTH[0x01] = { n: "Battery", t: uint8, u: "%" };
BTH[0x02] = { n: "Temperature", t: int16, f: 0.01 };
BTH[0x03] = { n: "Humidity", t: uint16, u: "%", f: 0.01 };
BTH[0x05] = { n: "Illuminance", t: uint24, f: 0.01 };
BTH[0x0a] = { n: "Energy", t: uint24, f: 0.001 };
BTH[0x0b] = { n: "Power", t: uint24, f: 0.01 };
BTH[0x0c] = { n: "Voltage", t: uint16, f: 0.001 };
BTH[0x10] = { n: "Power", t: uint8 };
BTH[0x1a] = { n: "Door", t: uint8 };
BTH[0x20] = { n: "Moisture", t: uint8 };
BTH[0x21] = { n: "Motion", t: uint8 };
BTH[0x14] = { n: "Moisture", t: uint16, f: 0.01 };
BTH[0x2f] = { n: "Moisture", t: uint8 };
BTH[0x2d] = { n: "Window", t: uint8 };
BTH[0x3a] = { n: "Button", t: uint8 };
BTH[0x3f] = { n: "Rotation", t: int16, f: 0.1 };
BTH[0x45] = { n: "Temperature", t: int16, f: 0.1 };

let BTHomeDecoder = {
  utoi: function (num, bitsz) {
    let mask = 1 << (bitsz - 1);
    return num & mask ? num - (1 << bitsz) : num;
  },
  getUInt8: function (buffer) {
    return buffer.at(0);
  },
  getInt8: function (buffer) {
    return this.utoi(this.getUInt8(buffer), 8);
  },
  getUInt16LE: function (buffer) {
    return 0xffff & ((buffer.at(1) << 8) | buffer.at(0));
  },
  getInt16LE: function (buffer) {
    return this.utoi(this.getUInt16LE(buffer), 16);
  },
  getUInt24LE: function (buffer) {
    return (
      0x00ffffff & ((buffer.at(2) << 16) | (buffer.at(1) << 8) | buffer.at(0))
    );
  },
  getInt24LE: function (buffer) {
    return this.utoi(this.getUInt24LE(buffer), 24);
  },
  getBufValue: function (type, buffer) {
    if (buffer.length < getByteSize(type)) return null;
    let res = null;
    if (type === uint8) res = this.getUInt8(buffer);
    if (type === int8) res = this.getInt8(buffer);
    if (type === uint16) res = this.getUInt16LE(buffer);
    if (type === int16) res = this.getInt16LE(buffer);
    if (type === uint24) res = this.getUInt24LE(buffer);
    if (type === int24) res = this.getInt24LE(buffer);
    return res;
  },
  unpackV1: function (res) {
    let buffer = res.service_data[BTHOME_V1_SVC_ID_STR];
    // beacons might not provide BTH service data
    if (typeof buffer !== "string" || buffer.length === 0) return null;
    let result = {};
    result["encryption"] = false;
    result["BTHome_version"] = 1;
    result["addr"] = res.addr;
    result["rssi"] = res.rssi;
    let i = 0;
    while (i < buffer.length) {
      let info = buffer.at(i);
      let type = info >> 5;
      let length = (info & 31);

      if (i + length > buffer.length || length < 2) {
        console.log("Invalid BTH v1 package");
        break;
      }

      let ident = buffer.at(i + 1);
      let value = buffer.slice(i + 2, i + 2 + length - 1);
      
      let dataType = -1;
      let dataLength = length - 1;
      if (dataLength === 1 && type === 0) {
        dataType = uint8;
      } else if (dataLength === 1 && type === 1) {
        dataType = int8;
      } else if (dataLength === 2 && type === 0) {
        dataType = uint16;
      } else if (dataLength === 2 && type === 1) {
        dataType = int16;
      } else if (dataLength === 3 && type === 0) {
        dataType = uint24;
      } else if (dataLength === 3 && type === 1) {
        dataType = int24;
      } else if (type === 2) {
        console.log("Unsupported BTH v1 type float");
      } else if (type === 3) {
        console.log("Unsupported BTH v1 type string");
      } else if (type === 4) {
        console.log("Unsupported BTH v1 type mac");
      }
      if (dataType >= 0) {
        value = BTHomeDecoder.getBufValue(dataType, value);
        let _bth = BTH[ident];
        if (typeof _bth === "undefined") {
          console.log("Unsupported BTH v1 ident", ident);
        } else {
          if (_bth.f) value *= _bth.f;
          result[_bth.n] = value;
        }
      }

      i += length + 1;
    }
    return result;
  },
  unpackV2: function (res) {
    let buffer = res.service_data[BTHOME_V2_SVC_ID_STR];
    // beacons might not provide BTH service data
    if (typeof buffer !== "string" || buffer.length === 0) return null;
    let result = {};
    result.addr = res.addr;
    result.rssi = res.rssi;
    let _dib = buffer.at(0);
    result["encryption"] = _dib & 0x1 ? true : false;
    result["BTHome_version"] = _dib >> 5;
    if (result["BTHome_version"] !== 2) return null;
    //Can not handle encrypted data
    if (result["encryption"]) return result;
    buffer = buffer.slice(1);

    let _bth;
    let _value;
    while (buffer.length > 0) {
      _bth = BTH[buffer.at(0)];
      if (typeof _bth === "undefined") {
        console.log("BTH: unknown type '" + buffer.at(0) + "''");
        break;
      }
      buffer = buffer.slice(1);
      _value = this.getBufValue(_bth.t, buffer);
      if (_value === null) break;
      if (typeof _bth.f !== "undefined") _value = _value * _bth.f;
      result[_bth.n] = _value;
      buffer = buffer.slice(getByteSize(_bth.t));
    }
    return result;
  },
};

function scanCB(ev, res) {
  if (ev !== BLE.Scanner.SCAN_RESULT) return;
  // skip if there is no service_data member
  if (typeof res.service_data === "undefined") return;

  let parsed;
  if (typeof res.service_data[BTHOME_V2_SVC_ID_STR] !== "undefined") {
    parsed = BTHomeDecoder.unpackV2(res);
  } else if (typeof res.service_data[BTHOME_V1_SVC_ID_STR] !== "undefined") {
    parsed = BTHomeDecoder.unpackV1(res);
  } else return;

  // skip if parsing failed
  if (parsed === null) {
    console.log("Failed to parse BTH data");
    return;
  }
  // skip, we are deduping results
  console.log("Shelly BTH packet: ", JSON.stringify(parsed));
  MQTT.publish("shellies/blu/" + res.addr, JSON.stringify(parsed), 0, false);
}

BLE.Scanner.Start({ duration_ms: SCAN_DURATION, active: ACTIVE_SCAN }, scanCB);