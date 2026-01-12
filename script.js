/* script.js - versi perbaikan penuh */

/* Fungsi dasar */
const load = (key) => JSON.parse(localStorage.getItem(key)) || [];
const save = (key, data) => localStorage.setItem(key, JSON.stringify(data));
const fmtDate = d => { if(!d) return ''; const dt=new Date(d); if(isNaN(dt)) return d; return dt.toLocaleDateString('id-ID'); };

/* Render tabel */
function render(key, tbodyId, columns, showActions = true) {
  const list = load(key);
  const tbody = document.getElementById(tbodyId);
  if(!tbody) return;
  tbody.innerHTML = '';
  list.forEach((item, i) => {
    const tr = document.createElement('tr');

    // Nomor
    tr.innerHTML = `<td>${i + 1}</td>`;

    // Kolom data
    columns.forEach(c => {
      const td = document.createElement('td');
      let v = item[c] ?? '';
      if (/tgl|tanggal/i.test(c)) v = fmtDate(v);
      td.textContent = v;
      tr.appendChild(td);
    });

    // Tombol aksi
    if (showActions) {
      const td = document.createElement('td');
      td.className = 'actions';

      const edit = document.createElement('button');
      edit.textContent = 'Edit';
      edit.className = 'btn-edit small';
      edit.onclick = () => editItem(key, i, columns, tbodyId);

      const del = document.createElement('button');
      del.textContent = 'Hapus';
      del.className = 'btn-delete small';
      del.onclick = () => deleteItem(key, i, columns, tbodyId);

      td.append(edit, del);
      tr.appendChild(td);
    }

    tbody.appendChild(tr);
  });
}

/* Hapus item */
function deleteItem(key, index, columns, tbodyId) {
  if (!confirm('Yakin ingin menghapus data ini?')) return;
  const arr = load(key);
  arr.splice(index, 1);
  save(key, arr);
  render(key, tbodyId, columns);
}

/* Edit item */
function editItem(key, index, columns, tbodyId) {
  const arr = load(key);
  const item = arr[index];
  const newItem = { ...item };

  for (const c of columns) {
    const val = prompt(`Ubah ${c}:`, item[c] ?? '');
    if (val !== null) newItem[c] = val;
  }

  arr[index] = newItem;
  save(key, arr);
  render(key, tbodyId, columns);
  alert('Data berhasil diperbarui ✅');
}

/* Inisialisasi halaman */
function initIndex() {
  const balita = load('balita').length;
  const ibu = load('ibu').length;
  const imun = load('imunisasi').length;
  const pert = load('pertumbuhan').length;
  const jad = load('jadwal').sort((a, b) => new Date(a.tanggal) - new Date(b.tanggal))[0];

  document.getElementById('cardBalita').textContent = balita;
  document.getElementById('cardIbu').textContent = ibu;
  document.getElementById('cardImun').textContent = imun;
  document.getElementById('nextJadwal').textContent = jad ? `${fmtDate(jad.tanggal)} — ${jad.tempat}` : 'Belum ada jadwal';
}

/* Halaman Balita */
function initBalita() {
  const key = 'balita', columns = ['nama', 'tanggal', 'jk', 'nama_ibu'];
  render(key, 'tbodyBalita', columns);

  const f = document.getElementById('balitaForm');
  f.onsubmit = e => {
    e.preventDefault();
    const data = {
      nama: f.nama.value.trim(),
      tanggal: f.tanggal.value,
      jk: f.jk.value,
      nama_ibu: f.nama_ibu.value.trim()
    };
    const arr = load(key);
    arr.push(data);
    save(key, arr);
    f.reset();
    render(key, 'tbodyBalita', columns);
    alert('Data Balita tersimpan ✅');
  };
}

/* Halaman Ibu */
function initIbu() {
  const key = 'ibu', columns = ['nama', 'usia', 'alamat'];
  render(key, 'tbodyIbu', columns);

  const f = document.getElementById('ibuForm');
  f.onsubmit = e => {
    e.preventDefault();
    const data = {
      nama: f.nama.value.trim(),
      usia: f.usia.value,
      alamat: f.alamat.value.trim()
    };
    const arr = load(key);
    arr.push(data);
    save(key, arr);
    f.reset();
    render(key, 'tbodyIbu', columns);
    alert('Data Ibu tersimpan ✅');
  };
}

/* Halaman Imunisasi */
function initImun() {
  const key = 'imunisasi', columns = ['nama_balita', 'jenis', 'tanggal'];
  render(key, 'tbodyImun', columns);

  const f = document.getElementById('imunForm');
  f.onsubmit = e => {
    e.preventDefault();
    const data = {
      nama_balita: f.nama.value.trim(),
      jenis: f.jenis.value.trim(),
      tanggal: f.tanggal.value
    };
    const arr = load(key);
    arr.push(data);
    save(key, arr);
    f.reset();
    render(key, 'tbodyImun', columns);
    alert('Data Imunisasi tersimpan ✅');
  };
}

/* Halaman Pertumbuhan */
function initGrowth() {
  const key = 'pertumbuhan', columns = ['nama', 'berat', 'tinggi', 'tanggal'];
  render(key, 'tbodyGrowth', columns);

  const f = document.getElementById('growthForm');
  f.onsubmit = e => {
    e.preventDefault();
    const data = {
      nama: f.nama.value.trim(),
      berat: f.berat.value,
      tinggi: f.tinggi.value,
      tanggal: f.tanggal.value
    };
    const arr = load(key);
    arr.push(data);
    save(key, arr);
    f.reset();
    render(key, 'tbodyGrowth', columns);
    alert('Data Pertumbuhan tersimpan ✅');
  };
}

/* Halaman Jadwal */
function initJadwal() {
  const key = 'jadwal', columns = ['tanggal', 'tempat', 'keterangan'];
  render(key, 'tbodyJadwal', columns);

  const f = document.getElementById('jadwalForm');
  f.onsubmit = e => {
    e.preventDefault();
    const data = {
      tanggal: f.tanggal.value,
      tempat: f.tempat.value.trim(),
      keterangan: f.keterangan.value.trim()
    };
    const arr = load(key);
    arr.push(data);
    save(key, arr);
    f.reset();
    render(key, 'tbodyJadwal', columns);
    alert('Jadwal tersimpan ✅');
  };
}

/* Halaman Laporan */
function initReport() {
  const balita = load('balita');
  const ibu = load('ibu');
  const imun = load('imunisasi');
  const pert = load('pertumbuhan');
  const jad = load('jadwal');
  const box = document.getElementById('laporanBox');

  if (!box) return;
  box.innerHTML = `
    <h3>Rekap Laporan Posyandu</h3>
    <p><b>Jumlah Balita:</b> ${balita.length}</p>
    <p><b>Jumlah Ibu Hamil:</b> ${ibu.length}</p>
    <p><b>Total Imunisasi:</b> ${imun.length}</p>
    <p><b>Total Pengukuran Pertumbuhan:</b> ${pert.length}</p>
    <p><b>Total Jadwal:</b> ${jad.length}</p>
    <button onclick="window.print()">🖨 Cetak Laporan</button>
  `;
}

/* Tentukan halaman */
function refresh() {
  const page = document.body.getAttribute('data-page');
  if (page === 'index') initIndex();
  if (page === 'balita') initBalita();
  if (page === 'ibu') initIbu();
  if (page === 'imunisasi') initImun();
  if (page === 'pertumbuhan') initGrowth();
  if (page === 'jadwal') initJadwal();
  if (page === 'laporan') initReport();
}

/* Jalankan otomatis */
document.addEventListener('DOMContentLoaded', refresh);
