function generate() {
    let n = +document.getElementById("qCount").value;
    let box = document.getElementById("questions");
    box.innerHTML = "";

    for (let i = 1; i <= n; i++) {
        box.innerHTML += `
        <div class="q-card mb-3">
            <div class="q-title">Q${i}</div>
            <input name="question[${i}]" class="form-control mb-2" required>

            ${["A", "B", "C", "D"].map(l => `
            <div class="option-row">
                <span class="option-label">${l}.</span>
                <input name="${l.toLowerCase()}[${i}]" class="form-control" required>
            </div>`).join("")}

            <select name="correct[${i}]" class="form-select mt-2" required>
                <option value="">Correct Option</option>
                <option value="a">A</option>
                <option value="b">B</option>
                <option value="c">C</option>
                <option value="d">D</option>
            </select>
        </div>`;
    }
}
generate();