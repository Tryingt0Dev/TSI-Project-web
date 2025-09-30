document.getElementById('busqueda').addEventListener('input', async function () {
    const query = this.value.trim();
    const resultados = document.getElementById('resultados');
    resultados.innerHTML = '';

    if (!query) return;

    const res = await fetch(`https://openlibrary.org/search.json?q=${encodeURIComponent(query)}&limit=15`);
    const data = await res.json();

    data.docs.slice(0, 15).forEach(book => {
        const option = document.createElement('option');
        option.value = book.key;
        option.textContent = `${book.title} - ${(book.author_name ? book.author_name[0] : 'Desconocido')}`;
        resultados.appendChild(option);
    });
});

document.getElementById('resultados').addEventListener('change', async function () {
    const selected = this.selectedOptions[0];
    if (!selected) return;

    const workKey = selected.value;
    const resWork = await fetch(`https://openlibrary.org${workKey}.json`);
    const details = await resWork.json();

    
    document.getElementById('titulo').value = details.title || '';

    
    if (details.subjects && details.subjects.length > 0) {
        document.getElementById('genero_autocomplete').value = details.subjects[0];
    }

    
    if (details.first_publish_date) {
        const year = details.first_publish_date.substring(0, 4);
        document.getElementById('fecha_publicacion').value = `${year}-01-01`;
    }

    
    if (details.authors && details.authors.length > 0) {
        const authorKey = details.authors[0].author.key;
        const resAuthor = await fetch(`https://openlibrary.org${authorKey}.json`);
        const authorData = await resAuthor.json();
        document.getElementById('autor_nombre').value = authorData.name || '';
    }

   
    const resEditions = await fetch(`https://openlibrary.org${workKey}/editions.json?limit=1`);
    const editionsData = await resEditions.json();

    if (editionsData.entries && editionsData.entries.length > 0) {
        const edition = editionsData.entries[0];

        if (edition.isbn_13) {
            document.getElementById('isbn').value = edition.isbn_13[0];
        } else if (edition.isbn_10) {
            document.getElementById('isbn').value = edition.isbn_10[0];
        }

        if (edition.publishers && edition.publishers.length > 0) {
            document.getElementById('editorial').value = edition.publishers[0];
        }
    }
    
});
