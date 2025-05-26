function getIdPeliculaFromUrl() {
  const params = new URLSearchParams(window.location.search);
  return params.get('id_pelicula');
}

document.addEventListener('DOMContentLoaded', async () => {
  const id_pelicula = getIdPeliculaFromUrl();
  const seccionFunciones = document.getElementById('funciones');
  const posterImg = document.getElementById('peliculaPoster');

  if (!id_pelicula) {
    seccionFunciones.innerHTML = "<div class='mensaje-error'>No se ha seleccionado ninguna película.</div>";
    return;
  }

  // Cargar datos de la película
  let pelicula;
  try {
    const resPeli = await fetch(`../php/peliculas.php`);
    const peliculas = await resPeli.json();
    pelicula = peliculas.find(p => p.id_pelicula == id_pelicula);
  } catch (e) {
    seccionFunciones.innerHTML = "<div class='mensaje-error'>Error al cargar los datos de la película.</div>";
    console.error(e);
    return;
  }

  if (!pelicula) {
    seccionFunciones.innerHTML = "<div class='mensaje-error'>Película no encontrada.</div>";
    return;
  }

  document.getElementById('peliculaTitulo').textContent = pelicula.titulo;
  document.getElementById('sinopsis').textContent = pelicula.sinopsis;
  document.getElementById('duracion').textContent = pelicula.duracion_minutos + " min";
  document.getElementById('sala').textContent = pelicula.sala || "Por asignar";
  document.getElementById('precio').textContent = pelicula.precio ? parseFloat(pelicula.precio).toFixed(2) : "Por definir";
  posterImg.src = pelicula.imagen_url || "../resources/peli_683293c9484d4.jpg";
  posterImg.alt = pelicula.titulo;

  // Cargar funciones de la película
  let funciones = [];
  try {
    const resFunciones = await fetch(`../php/funciones.php?id_pelicula=${id_pelicula}`);
    funciones = await resFunciones.json();
  } catch (e) {
    document.getElementById('funcionLista').innerHTML = "<div class='mensaje-error'>Error al cargar las funciones.</div>";
    console.error(e);
    return;
  }

  const funcionListaDiv = document.getElementById('funcionLista');
  funcionListaDiv.innerHTML = "";

  if (!funciones || funciones.length === 0) {
    funcionListaDiv.innerHTML = "<div class='mensaje-info'>No hay funciones programadas para esta película.</div>";
  } else {
    funciones.forEach(f => {
      const card = document.createElement('div');
      card.className = 'funcion-card';
      card.innerHTML = `
        <span class="funcion-hora">${new Date(f.fecha_hora).toLocaleString()}</span>
        <button class="reservar-funcion-btn" onclick="alert('Reservar para función: ${new Date(f.fecha_hora).toLocaleString()}')">Reservar</button>
      `;
      funcionListaDiv.appendChild(card);
    });
  }
});