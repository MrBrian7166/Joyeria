// carrito.js
document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const btnCarrito = document.querySelector('.btn-carrito');
    const carritoContainer = document.querySelector('.carrito-container');
    const overlay = document.querySelector('.overlay');
    const cerrarCarrito = document.querySelector('.cerrar-carrito');
    const carritoItems = document.getElementById('carrito-items');
    const carritoTotal = document.getElementById('carrito-total');
    const contadorCarrito = document.querySelector('.carrito-contador');
    const btnVaciar = document.getElementById('vaciar-carrito');
    
    // Inicializar carrito desde localStorage o crear uno vacío
    let carrito = JSON.parse(localStorage.getItem('carrito')) || [];

    // Mostrar/ocultar carrito
    function toggleCarrito() {
        carritoContainer.classList.toggle('abierto');
        overlay.classList.toggle('visible');
    }

    // Actualizar carrito en la UI
    function actualizarCarrito() {
        // Limpiar carrito
        carritoItems.innerHTML = '';

        // Actualizar items
        let total = 0;
        let totalItems = 0;

        carrito.forEach(item => {
            total += item.precio * item.cantidad;
            totalItems += item.cantidad;

            const itemHTML = document.createElement('div');
            itemHTML.classList.add('carrito-item');
            itemHTML.innerHTML = `
                <div class="carrito-item-img" style="background-image: url('${item.imagen}')"></div>
                <div class="carrito-item-info">
                    <h4 class="carrito-item-titulo">${item.nombre}</h4>
                    <p class="carrito-item-precio">${item.precio.toLocaleString()}€</p>
                    <div class="carrito-item-cantidad">
                        <button class="disminuir" data-id="${item.id}">-</button>
                        <input type="number" value="${item.cantidad}" min="1" data-id="${item.id}">
                        <button class="aumentar" data-id="${item.id}">+</button>
                    </div>
                    <button class="eliminar-item" data-id="${item.id}">Eliminar</button>
                </div>
            `;
            carritoItems.appendChild(itemHTML);
        });

        // Actualizar total
        carritoTotal.textContent = total.toLocaleString();
        contadorCarrito.textContent = totalItems;

        // Eventos para los botones de cantidad y eliminar
        document.querySelectorAll('.disminuir').forEach(btn => {
            btn.addEventListener('click', disminuirCantidad);
        });

        document.querySelectorAll('.aumentar').forEach(btn => {
            btn.addEventListener('click', aumentarCantidad);
        });

        document.querySelectorAll('.carrito-item-cantidad input').forEach(input => {
            input.addEventListener('change', cambiarCantidad);
        });

        document.querySelectorAll('.eliminar-item').forEach(btn => {
            btn.addEventListener('click', eliminarItem);
        });
    }

    // Funciones para manejar el carrito
    function disminuirCantidad(e) {
        const id = e.target.getAttribute('data-id');
        const item = carrito.find(item => item.id === id);
        
        if (item.cantidad > 1) {
            item.cantidad--;
        } else {
            eliminarItem(e);
            return;
        }
        
        guardarCarrito();
    }

    function aumentarCantidad(e) {
        const id = e.target.getAttribute('data-id');
        const item = carrito.find(item => item.id === id);
        item.cantidad++;
        guardarCarrito();
    }

    function cambiarCantidad(e) {
        const id = e.target.getAttribute('data-id');
        const nuevaCantidad = parseInt(e.target.value);
        
        if (nuevaCantidad < 1) {
            e.target.value = 1;
            return;
        }
        
        const item = carrito.find(item => item.id === id);
        item.cantidad = nuevaCantidad;
        guardarCarrito();
    }

    function eliminarItem(e) {
        const id = e.target.getAttribute('data-id');
        const index = carrito.findIndex(item => item.id === id);
        
        if (index !== -1) {
            carrito.splice(index, 1);
            guardarCarrito();
        }
    }

    function vaciarCarrito() {
        carrito = [];
        guardarCarrito();
        mostrarNotificacion('Carrito vaciado');
    }

    function guardarCarrito() {
        localStorage.setItem('carrito', JSON.stringify(carrito));
        actualizarCarrito();
    }

    function mostrarNotificacion(mensaje) {
        const notificacion = document.createElement('div');
        notificacion.className = 'notificacion-carrito';
        notificacion.textContent = mensaje;
        document.body.appendChild(notificacion);
        
        setTimeout(() => {
            notificacion.classList.add('mostrar');
            setTimeout(() => {
                notificacion.classList.remove('mostrar');
                setTimeout(() => {
                    document.body.removeChild(notificacion);
                }, 500);
            }, 3000);
        }, 100);
    }

    // Función global para agregar productos
    window.agregarAlCarrito = function(id, nombre, precio, imagen) {
        const productoExistente = carrito.find(item => item.id === id);

        if (productoExistente) {
            productoExistente.cantidad++;
        } else {
            carrito.push({
                id,
                nombre,
                precio: parseFloat(precio),
                imagen,
                cantidad: 1
            });
        }

        guardarCarrito();
        mostrarNotificacion(`${nombre} añadido al carrito`);
    };

    // Event listeners
    if (btnCarrito) btnCarrito.addEventListener('click', toggleCarrito);
    if (cerrarCarrito) cerrarCarrito.addEventListener('click', toggleCarrito);
    if (overlay) overlay.addEventListener('click', toggleCarrito);
    if (btnVaciar) btnVaciar.addEventListener('click', vaciarCarrito);

    // Inicializar
    actualizarCarrito();
});