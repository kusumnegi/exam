/* ===== MODAL ===== */
    const modal = document.getElementById("instructionModal");

    modal.addEventListener("show.bs.modal", e => {
      const b = e.relatedTarget;
      examId.value = b.dataset.exam;
      modalExamName.innerText = b.dataset.name;
      examDuration.innerText = b.dataset.duration;
      examEndDate.innerText = b.dataset.end;
      attemptText.innerText = `Attempt ${parseInt(b.dataset.used)+1} / ${b.dataset.max}`;
      agree.checked = false;
      startExamBtn.disabled = true;
    });

    agree.addEventListener("change", () => {
      startExamBtn.disabled = !agree.checked;
    });


    /* ===== SEARCH ===== */
    const searchInput = document.getElementById("searchInput");
    const suggestions = document.getElementById("suggestions");
    const cards = document.querySelectorAll(".exam-card");
    const cols = document.querySelectorAll(".exam-col");
    const noResult = document.getElementById("noResult");

    searchInput.addEventListener("input", () => {
      const val = searchInput.value.toLowerCase().trim();
      suggestions.innerHTML = "";
      let found = 0;

      cards.forEach((c, i) => {
        if (c.dataset.name.includes(val)) {
          cols[i].style.display = "block";
          found++;
          if (val) {
            const item = document.createElement("button");
            item.className = "list-group-item list-group-item-action";
            item.textContent = c.dataset.name;
            item.onclick = () => {
              searchInput.value = c.dataset.name;
              filter(c.dataset.name);
              suggestions.classList.add("d-none");
            };
            suggestions.appendChild(item);
          }
        } else cols[i].style.display = "none";
      });

      noResult.classList.toggle("d-none", found > 0);
      suggestions.classList.toggle("d-none", !val || !suggestions.children.length);
    });

    function filter(v) {
      let f = 0;
      cards.forEach((c, i) => {
        if (c.dataset.name.includes(v)) {
          cols[i].style.display = "block";
          f++;
        } else cols[i].style.display = "none";
      });
      noResult.classList.toggle("d-none", f > 0);
    }

    document.addEventListener("click", e => {
      if (!e.target.closest(".search-box"))
        suggestions.classList.add("d-none");
    });



    
