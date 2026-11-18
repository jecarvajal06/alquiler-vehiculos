const API_VEHICULOS = 'http://localhost:8001/index.php';
const API_CLIENTES = 'http://localhost:8002/index.php';
const API_RESERVAS = 'http://localhost:8003/index.php';

let vehiculos = [];
let clientes = [];

// ---------- utilidades ----------

function mostrarAviso(texto, tipo) {
  const aviso = document.getElementById('aviso');
  aviso.textContent = texto;
  aviso.className = 'aviso ' + tipo;
  setTimeout(() => aviso.classList.add('oculto'), 4000);
}

// hace la peticion y lanza error si la respuesta no es 2xx
async function pedir(url, metodo = 'GET', cuerpo = null) {
  const opciones = { method: metodo };
  if (cuerpo) {
    opciones.headers = { 'Content-Type': 'application/json' };
    opciones.body = JSON.stringify(cuerpo);
  }
  const resp = await fetch(url, opciones);
  let datos = null;
  try {
    datos = await resp.json();
  } catch (e) {
    datos = null;
  }
  if (!resp.ok) {
    throw new Error(datos && datos.error ? datos.error : 'Error ' + resp.status);
  }
  return datos;
}

// ejecuta una accion y muestra el error si falla
async function intentar(funcion, mensajeOk) {
  try {
    await funcion();
    if (mensajeOk) mostrarAviso(mensajeOk, 'ok');
  } catch (e) {
    mostrarAviso(e.message, 'error');
  }
}

function etiqueta(estado) {
  return '<span class="etiqueta ' + estado + '">' + estado + '</span>';
}

function texto(valor) {
  const div = document.createElement('div');
  div.textContent = valor == null ? '' : valor;
  return div.innerHTML;
}

// ---------- pestañas ----------

document.querySelectorAll('.tab').forEach(boton => {
  boton.addEventListener('click', () => {
    document.querySelectorAll('.tab').forEach(b => b.classList.remove('activo'));
    document.querySelectorAll('.seccion').forEach(s => s.classList.add('oculto'));
    boton.classList.add('activo');
    document.getElementById(boton.dataset.seccion).classList.remove('oculto');
  });
});

// ---------- vehiculos ----------

async function cargarVehiculos() {
  vehiculos = await pedir(API_VEHICULOS);
  const cuerpo = document.getElementById('tabla-vehiculos');
  cuerpo.innerHTML = '';
  vehiculos.forEach(v => {
    const fila = document.createElement('tr');
    fila.innerHTML =
      '<td>' + v.id + '</td><td>' + texto(v.marca) + '</td><td>' + texto(v.modelo) + '</td>' +
      '<td>' + v.anio + '</td><td>' + texto(v.categoria) + '</td><td>' + etiqueta(v.estado) + '</td>' +
      '<td><button class="editar">Editar</button><button class="peligro eliminar">Eliminar</button></td>';
    fila.querySelector('.editar').addEventListener('click', () => editarVehiculo(v));
    fila.querySelector('.eliminar').addEventListener('click', () => eliminarVehiculo(v.id));
    cuerpo.appendChild(fila);
  });
}

function editarVehiculo(v) {
  document.getElementById('veh-id').value = v.id;
  document.getElementById('veh-marca').value = v.marca;
  document.getElementById('veh-modelo').value = v.modelo;
  document.getElementById('veh-anio').value = v.anio;
  document.getElementById('veh-categoria').value = v.categoria;
  document.getElementById('veh-estado').value = v.estado;
  document.getElementById('veh-guardar').textContent = 'Actualizar';
}

function limpiarVehiculo() {
  document.getElementById('form-vehiculo').reset();
  document.getElementById('veh-id').value = '';
  document.getElementById('veh-guardar').textContent = 'Guardar';
}

document.getElementById('veh-cancelar').addEventListener('click', limpiarVehiculo);

document.getElementById('form-vehiculo').addEventListener('submit', e => {
  e.preventDefault();
  intentar(async () => {
    const id = document.getElementById('veh-id').value;
    const datos = {
      marca: document.getElementById('veh-marca').value,
      modelo: document.getElementById('veh-modelo').value,
      anio: parseInt(document.getElementById('veh-anio').value),
      categoria: document.getElementById('veh-categoria').value,
      estado: document.getElementById('veh-estado').value
    };
    if (id) {
      await pedir(API_VEHICULOS + '?id=' + id, 'PUT', datos);
    } else {
      await pedir(API_VEHICULOS, 'POST', datos);
    }
    limpiarVehiculo();
    await recargarTodo();
  }, 'Vehículo guardado');
});

function eliminarVehiculo(id) {
  if (!confirm('¿Eliminar este vehículo?')) return;
  intentar(async () => {
    await pedir(API_VEHICULOS + '?id=' + id, 'DELETE');
    await recargarTodo();
  }, 'Vehículo eliminado');
}

// ---------- clientes ----------

async function cargarClientes() {
  clientes = await pedir(API_CLIENTES);
  const cuerpo = document.getElementById('tabla-clientes');
  cuerpo.innerHTML = '';
  clientes.forEach(c => {
    const fila = document.createElement('tr');
    fila.innerHTML =
      '<td>' + c.id + '</td><td>' + texto(c.nombre) + '</td><td>' + texto(c.telefono) + '</td>' +
      '<td>' + texto(c.correo) + '</td><td>' + texto(c.numero_licencia) + '</td>' +
      '<td><button class="editar">Editar</button><button class="peligro eliminar">Eliminar</button></td>';
    fila.querySelector('.editar').addEventListener('click', () => editarCliente(c));
    fila.querySelector('.eliminar').addEventListener('click', () => eliminarCliente(c.id));
    cuerpo.appendChild(fila);
  });
}

function editarCliente(c) {
  document.getElementById('cli-id').value = c.id;
  document.getElementById('cli-nombre').value = c.nombre;
  document.getElementById('cli-telefono').value = c.telefono;
  document.getElementById('cli-correo').value = c.correo;
  document.getElementById('cli-licencia').value = c.numero_licencia;
  document.getElementById('cli-guardar').textContent = 'Actualizar';
}

function limpiarCliente() {
  document.getElementById('form-cliente').reset();
  document.getElementById('cli-id').value = '';
  document.getElementById('cli-guardar').textContent = 'Guardar';
}

document.getElementById('cli-cancelar').addEventListener('click', limpiarCliente);

document.getElementById('form-cliente').addEventListener('submit', e => {
  e.preventDefault();
  intentar(async () => {
    const id = document.getElementById('cli-id').value;
    const datos = {
      nombre: document.getElementById('cli-nombre').value,
      telefono: document.getElementById('cli-telefono').value,
      correo: document.getElementById('cli-correo').value,
      numero_licencia: document.getElementById('cli-licencia').value
    };
    if (id) {
      await pedir(API_CLIENTES + '?id=' + id, 'PUT', datos);
    } else {
      await pedir(API_CLIENTES, 'POST', datos);
    }
    limpiarCliente();
    await recargarTodo();
  }, 'Cliente guardado');
});

function eliminarCliente(id) {
  if (!confirm('¿Eliminar este cliente?')) return;
  intentar(async () => {
    await pedir(API_CLIENTES + '?id=' + id, 'DELETE');
    await recargarTodo();
  }, 'Cliente eliminado');
}

// ---------- reservas ----------

function llenarSelects() {
  const selCliente = document.getElementById('res-cliente');
  const selVehiculo = document.getElementById('res-vehiculo');
  selCliente.innerHTML = clientes.map(c =>
    '<option value="' + c.id + '">' + texto(c.nombre) + '</option>').join('');
  selVehiculo.innerHTML = vehiculos.map(v =>
    '<option value="' + v.id + '">' + texto(v.marca + ' ' + v.modelo) + ' (' + v.estado + ')</option>').join('');
  llenarElementoHistorial();
}

function filaReserva(r, conAcciones) {
  const fila = document.createElement('tr');
  let html =
    '<td>' + r.id + '</td><td>' + texto(r.cliente) + '</td><td>' + texto(r.vehiculo) + '</td>' +
    '<td>' + r.fecha_inicio + '</td><td>' + r.fecha_fin + '</td><td>' + etiqueta(r.estado) + '</td>';
  if (conAcciones) {
    html += '<td>';
    if (r.estado === 'activa') {
      html += '<button class="devolver">Devolver</button><button class="peligro cancelar">Cancelar</button>';
    }
    html += '</td>';
  }
  fila.innerHTML = html;
  if (conAcciones && r.estado === 'activa') {
    fila.querySelector('.devolver').addEventListener('click', () => cambiarReserva(r.id, 'devolver'));
    fila.querySelector('.cancelar').addEventListener('click', () => cambiarReserva(r.id, 'cancelar'));
  }
  return fila;
}

async function cargarReservas() {
  const reservas = await pedir(API_RESERVAS);
  const cuerpo = document.getElementById('tabla-reservas');
  cuerpo.innerHTML = '';
  reservas.forEach(r => cuerpo.appendChild(filaReserva(r, true)));
}

function cambiarReserva(id, accion) {
  intentar(async () => {
    await pedir(API_RESERVAS + '?id=' + id + '&accion=' + accion, 'PUT');
    await recargarTodo();
  }, accion === 'devolver' ? 'Vehículo devuelto' : 'Reserva cancelada');
}

document.getElementById('form-reserva').addEventListener('submit', e => {
  e.preventDefault();
  intentar(async () => {
    const datos = {
      cliente_id: parseInt(document.getElementById('res-cliente').value),
      vehiculo_id: parseInt(document.getElementById('res-vehiculo').value),
      fecha_inicio: document.getElementById('res-inicio').value,
      fecha_fin: document.getElementById('res-fin').value
    };
    await pedir(API_RESERVAS, 'POST', datos);
    document.getElementById('form-reserva').reset();
    await recargarTodo();
  }, 'Reserva creada');
});

// ---------- disponibilidad ----------

document.getElementById('disp-consultar').addEventListener('click', () => {
  const fecha = document.getElementById('disp-fecha').value;
  if (!fecha) {
    mostrarAviso('Elige una fecha', 'error');
    return;
  }
  intentar(async () => {
    const lista = await pedir(API_VEHICULOS + '?disponibles=' + fecha);
    const cuerpo = document.getElementById('tabla-disponibles');
    cuerpo.innerHTML = '';
    if (lista.length === 0) {
      cuerpo.innerHTML = '<tr><td colspan="6">No hay vehículos disponibles ese día</td></tr>';
    }
    lista.forEach(v => {
      const fila = document.createElement('tr');
      fila.innerHTML =
        '<td>' + v.id + '</td><td>' + texto(v.marca) + '</td><td>' + texto(v.modelo) + '</td>' +
        '<td>' + v.anio + '</td><td>' + texto(v.categoria) + '</td><td>' + etiqueta(v.estado) + '</td>';
      cuerpo.appendChild(fila);
    });
  });
});

// ---------- historial ----------

function llenarElementoHistorial() {
  const tipo = document.getElementById('hist-tipo').value;
  const select = document.getElementById('hist-elemento');
  if (tipo === 'cliente') {
    select.innerHTML = clientes.map(c =>
      '<option value="' + c.id + '">' + texto(c.nombre) + '</option>').join('');
  } else {
    select.innerHTML = vehiculos.map(v =>
      '<option value="' + v.id + '">' + texto(v.marca + ' ' + v.modelo) + '</option>').join('');
  }
  cargarHistorial();
}

async function cargarHistorial() {
  const tipo = document.getElementById('hist-tipo').value;
  const id = document.getElementById('hist-elemento').value;
  const cuerpo = document.getElementById('tabla-historial');
  cuerpo.innerHTML = '';
  if (!id) return;
  const parametro = tipo === 'cliente' ? 'cliente_id' : 'vehiculo_id';
  await intentar(async () => {
    const lista = await pedir(API_RESERVAS + '?' + parametro + '=' + id);
    lista.forEach(r => cuerpo.appendChild(filaReserva(r, false)));
  });
}

document.getElementById('hist-tipo').addEventListener('change', llenarElementoHistorial);
document.getElementById('hist-elemento').addEventListener('change', cargarHistorial);

// ---------- inicio ----------

async function recargarTodo() {
  await cargarVehiculos();
  await cargarClientes();
  llenarSelects();
  await cargarReservas();
}

intentar(recargarTodo);
