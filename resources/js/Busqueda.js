// public/js/busqueda.js
document.addEventListener('DOMContentLoaded', function () {
    // elementos
    const busquedaInput = document.getElementById('busqueda');
    const resultados = document.getElementById('resultados');
    const buscarBtn = document.getElementById('buscarBtn');

    const isbnInput = document.getElementById('isbn');
    const tituloInput = document.getElementById('titulo');
    const editorialInput = document.getElementById('editorial');
    const fechaInput = document.getElementById('fecha_publicacion');

    // autores area (múltiples)
    const authorsWrapper = document.getElementById('authors-wrapper');
    const autorNombreHidden = document.getElementById('autor_nombre_input'); // campo fallback (hidden)

    // debounce helper
    function debounce(fn, delay = 300) {
        let t;
        return function (...args) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    // función para crear input de autor (coincidente con la vista)
    function createAuthorInput(value = '') {
        const group = document.createElement('div');
        group.className = 'input-group mb-2 author-item';

        const input = document.createElement('input');
        input.type = 'text';
        input.name = 'autor_nombres[]';
        input.className = 'form-control';
        input.placeholder = 'Nombre autor';
        input.value = value;

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-outline-danger btn-remove-author';
        btn.title = 'Eliminar autor';
        btn.innerHTML = '<i class="bi bi-x-lg"></i>';

        btn.addEventListener('click', function () {
            group.remove();
            updateFallbackField();
        });

        group.appendChild(input);
        group.appendChild(btn);

        return group;
    }

    // Actualiza el hidden autor_nombre con la lista coma-separated
    function updateFallbackField() {
        if (!autorNombreHidden) return;
        const inputs = authorsWrapper.querySelectorAll('input[name="autor_nombres[]"]');
        const vals = [];
        inputs.forEach(i => {
            if (i.value && i.value.trim() !== '') vals.push(i.value.trim());
        });
        autorNombreHidden.value = vals.join(', ');
    }

    // Asegurar que botones eliminar pre-existentes tengan handler
    function initExistingRemoveButtons() {
        document.querySelectorAll('.btn-remove-author').forEach(btn => {
            if (btn.dataset.bound) return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', function (e) {
                const item = e.target.closest('.author-item');
                if (item) item.remove();
                updateFallbackField();
            });
        });
    }
    initExistingRemoveButtons();

    // Añadir autor existente desde select (si existe)
    const btnAddExisting = document.getElementById('btn-add-existing');
    const autorSelectExisting = document.getElementById('autor_select_existing');
    if (btnAddExisting && autorSelectExisting) {
        btnAddExisting.addEventListener('click', function () {
            const selected = autorSelectExisting.value;
            if (!selected) return;
            // prevenir duplicados: si ya existe ese valor en inputs, no añadir
            const exists = Array.from(authorsWrapper.querySelectorAll('input[name="autor_nombres[]"]'))
                .some(i => i.value.trim().toLowerCase() === selected.trim().toLowerCase());
            if (!exists) {
                authorsWrapper.appendChild(createAuthorInput(selected));
                updateFallbackField();
                initExistingRemoveButtons();
            }
            autorSelectExisting.selectedIndex = 0;
        });
    }

    // botón add manual (si existe)
    const btnAdd = document.getElementById('btn-add-author');
    if (btnAdd) {
        btnAdd.addEventListener('click', function () {
            authorsWrapper.appendChild(createAuthorInput(''));
            initExistingRemoveButtons();
            updateFallbackField();
        });
    }

    // actualizar fallback cuando hay cambios en el area de autores
    if (authorsWrapper) {
        authorsWrapper.addEventListener('input', debounce(function () {
            updateFallbackField();
        }, 120));
    }

    // BUSCAR en OpenLibrary
    async function buscarOpenLibrary(q) {
        if (!q || q.trim().length < 2) {
            resultados.innerHTML = '';
            resultados.style.display = 'none';
            return;
        }

        try {
            const url = `https://openlibrary.org/search.json?q=${encodeURIComponent(q)}&limit=15`;
            const res = await fetch(url);
            if (!res.ok) throw new Error('OpenLibrary no responde');

            const data = await res.json();
            resultados.innerHTML = '';

            if (!data.docs || data.docs.length === 0) {
                resultados.style.display = 'none';
                return;
            }

            // poblar select con opciones
            data.docs.slice(0, 15).forEach((book, idx) => {
                const option = document.createElement('option');
                // guardamos work key o edition key (preferimos work)
                // book.key suele ser '/works/OLxxxxxW'
                option.value = book.key || '';
                option.textContent = `${book.title || '—'} — ${(book.author_name ? book.author_name.slice(0,2).join(', ') : 'Desconocido')}${book.first_publish_year ? ' ('+book.first_publish_year+')' : ''}`;
                // datos útiles en dataset
                option.dataset.title = book.title || '';
                option.dataset.authors = (book.author_name || []).join(', ');
                option.dataset.first_publish_year = book.first_publish_year || '';
                option.dataset.isbn = (Array.isArray(book.isbn) && book.isbn.length) ? book.isbn[0] : '';
                resultados.appendChild(option);
            });

            resultados.size = Math.min(8, data.docs.length);
            resultados.style.display = 'block';
            resultados.focus();

            // al seleccionar con click/change se usará el código de abajo
        } catch (err) {
            console.warn('Error OpenLibrary:', err);
            resultados.innerHTML = '';
            resultados.style.display = 'none';
        }
    }

    const buscarDebounced = debounce((e) => buscarOpenLibrary(e.target.value), 300);

    if (busquedaInput) {
        busquedaInput.addEventListener('input', buscarDebounced);
        if (buscarBtn) {
            buscarBtn.addEventListener('click', () => buscarOpenLibrary(busquedaInput.value));
        }
    }

    // Manejar selección en el select resultados
    if (resultados) {
        resultados.addEventListener('change', async function () {
            const sel = resultados.selectedOptions[0];
            if (!sel) return;
            const workKey = sel.value; // e.g. /works/OLxxxxW

            try {
                // 1) cargar datos del work (título, listado de authors)
                const resWork = await fetch(`https://openlibrary.org${workKey}.json`);
                if (resWork.ok) {
                    const details = await resWork.json();
                    if (details.title) tituloInput.value = details.title;
                    // si el work trae subjects, ponemos el primero como genero_hint
                    if (details.subjects && details.subjects.length > 0) {
                        document.getElementById('genero_autocomplete').value = details.subjects[0];
                    }
                    if (details.first_publish_date) {
                        const year = String(details.first_publish_date).substring(0,4);
                        if (year.length===4) fechaInput.value = `${year}-01-01`;
                    } else if (sel.dataset.first_publish_year) {
                        const y = String(sel.dataset.first_publish_year);
                        if (y.length===4) fechaInput.value = `${y}-01-01`;
                    }

                    // 2) obtener autores del work (puede ser array con {author: {key:'/authors/OL...A'}} )
                    let authorNames = [];
                    if (details.authors && details.authors.length > 0) {
                        // recorrer y pedir cada author
                        for (const a of details.authors.slice(0,5)) {
                            if (a.author && a.author.key) {
                                try {
                                    const resA = await fetch(`https://openlibrary.org${a.author.key}.json`);
                                    if (resA.ok) {
                                        const ad = await resA.json();
                                        if (ad && ad.name) authorNames.push(ad.name);
                                    }
                                } catch (e) { /* ignore single author errors */ }
                            }
                        }
                    } else if (sel.dataset.authors) {
                        // fallback a author_name list from search result
                        authorNames = sel.dataset.authors ? sel.dataset.authors.split(',').map(s => s.trim()).filter(Boolean) : [];
                    }

                    // 3) si encontramos autores, añadirlos al authors-wrapper (evitamos duplicados)
                    if (authorNames.length > 0) {
                        authorNames.forEach(name => {
                            // no añadir vacíos
                            if (!name || name.trim() === '') return;
                            const exists = Array.from(authorsWrapper.querySelectorAll('input[name="autor_nombres[]"]'))
                                .some(i => i.value.trim().toLowerCase() === name.trim().toLowerCase());
                            if (!exists) {
                                authorsWrapper.appendChild(createAuthorInput(name));
                            }
                        });
                        initExistingRemoveButtons();
                        updateFallbackField();
                    }

                } // end resWork.ok

                // 3) buscar editions para coger ISBN y editorial si falta
                try {
                    const resEditions = await fetch(`https://openlibrary.org${workKey}/editions.json?limit=5`);
                    if (resEditions.ok) {
                        const editionsData = await resEditions.json();
                        if (editionsData && editionsData.entries && editionsData.entries.length > 0) {
                            // preferir una edition que tenga isbn_13 o isbn_10
                            let chosen = null;
                            for (const ed of editionsData.entries) {
                                if (ed.isbn_13 || ed.isbn_10) { chosen = ed; break; }
                            }
                            if (!chosen) chosen = editionsData.entries[0];

                            if (chosen) {
                                // ISBN
                                if (chosen.isbn_13 && chosen.isbn_13.length) {
                                    isbnInput.value = chosen.isbn_13[0];
                                } else if (chosen.isbn_10 && chosen.isbn_10.length) {
                                    isbnInput.value = chosen.isbn_10[0];
                                } else if (sel.dataset.isbn) {
                                    isbnInput.value = sel.dataset.isbn;
                                }

                                // editorial / publisher
                                if (chosen.publishers && chosen.publishers.length) {
                                    editorialInput.value = chosen.publishers[0];
                                }
                            }
                        }
                    }
                } catch (err) {
                    console.warn('No se pudo obtener editions:', err);
                }

            } catch (err) {
                console.warn('Error al cargar work/author:', err);
            } finally {
                // ocultar resultados tras seleccionar
                resultados.style.display = 'none';
            }
        });
    }

    // click afuera oculta
    document.addEventListener('click', function (e) {
        if (!resultados) return;
        if (!resultados.contains(e.target) && e.target !== busquedaInput && e.target !== buscarBtn) {
            resultados.style.display = 'none';
        }
    });

    // Si quieres, al doble click también seleccionar
    resultados.addEventListener('dblclick', function () {
        const opt = resultados.selectedOptions[0];
        if (opt) {
            const ev = new Event('change');
            resultados.dispatchEvent(ev);
        }
    });

    // inicializar fallback hidden al cargar (por si hay old values)
    updateFallbackField();
    
});

document.addEventListener('DOMContentLoaded', function () {
    const btnCrear = document.getElementById('btn-crear-genero');
    const inputGenero = document.getElementById('genero_autocomplete');
    const selectGenero = document.getElementById('genero_select');
    const hiddenGenero = document.getElementById('genero_nombre_hidden');

    if (!btnCrear || !inputGenero || !selectGenero) return;

    btnCrear.addEventListener('click', async function (e) {
        e.preventDefault(); // seguridad: evita cualquier envío por accidente
        const nombre = (inputGenero.value || '').trim();
        if (!nombre) {
            alert('Escribe el nombre del género que quieres crear.');
            inputGenero.focus();
            return;
        }

        // si ya existe en la lista, seleccionarlo
        const existing = Array.from(selectGenero.options).find(o => (o.text || '').toLowerCase() === nombre.toLowerCase());
        if (existing) {
            selectGenero.value = existing.value;
            if (hiddenGenero) hiddenGenero.value = existing.text;
            inputGenero.value = '';
            return alert('Género ya existente — seleccionado en la lista.');
        }

        // URL desde la variable global que pusimos en la vista
        const url = window?.appRoutes?.generoStore;
        if (!url) {
            console.error('Falta window.appRoutes.generoStore');
            alert('Configuración incorrecta: falta ruta para crear géneros.');
            return;
        }

        // CSRF token desde meta
        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        const token = tokenMeta ? tokenMeta.getAttribute('content') : null;

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    ...(token ? { 'X-CSRF-TOKEN': token } : {})
                },
                body: JSON.stringify({ nombre })
            });

            const payload = await res.json().catch(()=>null);

            if (!res.ok) {
                const msg = (payload && (payload.message || (payload.errors && Object.values(payload.errors).flat().join(', ')))) || 'Error al crear género';
                return alert(msg);
            }

            // agregar la nueva opción y seleccionarla
            const data = payload;
            const opt = document.createElement('option');
            opt.value = data.id;    // asumo que el controller devuelve { id, nombre }
            opt.text  = data.nombre;
            selectGenero.appendChild(opt);
            selectGenero.value = data.id;
            if (hiddenGenero) hiddenGenero.value = data.nombre;
            inputGenero.value = '';
            alert('Género creado y seleccionado: ' + data.nombre);
        } catch (err) {
            console.error('Error creando género:', err);
            alert('Error de red al crear el género. Revisa la consola.');
        }
    });
});