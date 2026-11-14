/** Types shared between server and client. */
export type TableState = "free" | "busy" | "soon";

export interface TableStatusPublic {
  id: string;
  name: string;
  type: string;
  capacity: number;
  state: TableState;
  busyUntil: string | null; // ISO
  nextStart: string | null; // ISO
}

export interface TableStatusAdmin extends TableStatusPublic {
  current: { id: number; name: string; phone: string; guests: number; startAt: string; endAt: string; status: string } | null;
  next: { id: number; name: string; phone: string; startAt: string; endAt: string; status: string } | null;
}


export interface BusyInterval {
  tableId: string;
  start: string;
  end: string;
}

export interface AdminBooking {
  id: number;
  tableId: string;
  tableName: string;
  name: string;
  phone: string;
  guests: number;
  comment: string | null;
  startAt: string;
  endAt: string;
  status: "PENDING" | "CONFIRMED" | "COMPLETED" | "CANCELLED";
  source: "WEBSITE" | "ADMIN";
  createdAt: string;
}
