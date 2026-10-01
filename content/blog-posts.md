# Alpha Preclinical Blog Posts

Oct 1, 2026 · @Jeremy Anderson

Seven launch posts for the Alpha Preclinical blog, each written to rank for a search a biotech scientist actually types, and each bylined to the team member best placed to own it.

## Before publishing

Each post is a draft in the named author's voice. The science is general and checked against published sources, but the author should read it, correct anything that doesn't match how Alpha runs studies, and add one detail from their own experience. That edit is what makes the post credible, and Google rewards first-hand expertise.

- [ ] Author reviews and approves the post
- [ ] Title tag, meta description and slug entered in Yoast or Rank Math
- [ ] Internal links added as listed in the post's SEO block
- [ ] Author bio box linked to their Team page profile
- [ ] Featured image from Alpha's own lab photos, with descriptive alt text
- [ ] Article schema with the author, published date and Alpha as publisher

Publish one post every one to two weeks rather than all seven at once. A steady cadence signals an active site, and each post can be shared on LinkedIn as it goes live.

## How the SEO block works

Every post opens with a short block for the WordPress build: the title tag (under 60 characters), meta description (under 160), URL slug, the primary keyword the post targets, and the Alpha pages it should link to. The H1 on the page is the post title; section headings inside each post become H2s.

## Post 1: How to scope your first in vivo efficacy study

| SEO field | Value |
| --- | --- |
| Author | Barak Yahalom, DVM |
| Title tag | How to Scope an In Vivo Efficacy Study \| Alpha Preclinical |
| Meta description | Plan your first in vivo efficacy study: define the decision, pick the model and endpoints, size groups and set a timeline before you request a CRO quote. |
| Slug | /blog/how-to-scope-in-vivo-efficacy-study |
| Primary keyword | in vivo efficacy study |
| Secondary keywords | preclinical study design, CRO study quote, animal model selection, group size |
| Internal links | PK/PD page, each disease model page, Contact page |

The most expensive in vivo study is the one that answers the wrong question. Before you compare CRO quotes, spend an hour on five decisions that set the model, group size, endpoints, timeline and budget. Get them right and the quote you receive will match the study you need.

### 1. Start with the decision, not the experiment

Write one sentence that names the decision this study informs. "Should we advance compound A over compound B into IND-enabling work?" leads to a very different design than "Does our lead show any activity in vivo?"

A go/no-go decision needs a clear primary endpoint and enough animals to detect the effect size that would change your mind. An exploratory question can tolerate smaller groups and more endpoints. Naming the decision first keeps the design honest.

### 2. Know your exposure before you test efficacy

An efficacy study that fails because the compound never reached its target tells you nothing about the biology. If you don't yet know how your molecule behaves in the species you plan to use, run a short pharmacokinetic study first.

A small PK study answers three questions: what dose and route give exposure above your in vitro potency, how long that exposure lasts, and whether the formulation is tolerated. Those answers set the dose levels and dosing frequency for the efficacy study. Many programs save weeks by pairing a PK satellite group with the efficacy arm.

### 3. Choose the model that matches your mechanism

No animal model reproduces a human disease completely. The right model is the one that shares the biology your drug acts on. Ask of any candidate model:

- Does it express the target, and is the pathway active?
- Does the disease develop on a timeline that fits your budget?
- Has a reference compound worked in it, so you have a positive control?
- Is the readout you care about measurable in this species?

Induced models (a diet, a chemical, an implanted tumor) are usually faster and more uniform. Spontaneous and genetic models often reflect human disease more closely but take longer and vary more between animals. Your CRO should be able to explain why it recommends one over the other.

### 4. Decide the endpoints and the group size together

Pick one primary endpoint that answers the decision from step one. Secondary endpoints are welcome, but each adds cost, and too many invite chasing noise.

Group size follows from that primary endpoint: how variable it is in this model, and how large an effect you need to detect. A power calculation using variability from prior studies in the same model is the standard approach. Underpowered studies are a common reason preclinical results fail to repeat.

Plan the controls at the same time. Most efficacy studies need a vehicle group and, where one exists, a positive control. Without them, a negative result can't tell you whether the drug failed or the model did.

### 5. Build in rigor from the start

The [ARRIVE 2.0 guidelines](https://arriveguidelines.org/arrive-guidelines) list ten essential items every animal study should report, including sample size, inclusion and exclusion criteria, randomization, blinding and statistical methods. Designing for them up front costs little and makes your data far more convincing to investors, partners and regulators.

In practice that means randomizing animals to groups after baseline measurements, blinding whoever scores the outcome where possible, and writing down exclusion criteria before the study starts.

### What to send a CRO for an accurate quote

A quote is only as good as the brief behind it. Include:

- The decision the study informs
- Compound or modality, route, and any PK or tolerability data you have
- Preferred model, or the mechanism if you want a recommendation
- Primary and secondary endpoints
- Number of test articles and dose levels
- Your target start date and when you need data

If you're not sure about some of these, say so. A good CRO partner will help you fill the gaps before quoting rather than after.

*Planning a study? Tell us about your program and an Alpha scientist will help you scope it.*

---

## Post 2: Caliper or IVIS? Choosing how to measure tumor burden

| SEO field | Value |
| --- | --- |
| Author | Barak Yahalom, DVM |
| Title tag | Caliper vs IVIS: Measuring Tumor Burden in Mice \| Alpha Preclinical |
| Meta description | When are caliper measurements enough, and when does bioluminescent IVIS imaging earn its cost? A practical guide for oncology efficacy studies. |
| Slug | /blog/caliper-vs-ivis-tumor-burden |
| Primary keyword | tumor volume caliper vs bioluminescence imaging |
| Secondary keywords | IVIS imaging, tumor burden mice, xenograft tumor volume, orthotopic tumor model |
| Internal links | Tumor models page, IVIS imaging service, Contact page |

For a subcutaneous tumor you can see and touch, calipers are usually enough. For a tumor inside an organ, or one that spreads, you need imaging. Most oncology efficacy studies fall clearly on one side of that line, and the ones that don't benefit from using both.

### How caliper measurement works, and where it falls short

A technician measures the tumor's length and width with digital calipers, and volume is estimated as an ellipsoid: volume = (length × width²) / 2. It's fast, cheap, needs no anesthesia, and can be repeated two or three times a week across hundreds of animals.

Its limits are well documented. In a study comparing methods against microCT, [caliper measurements overestimated subcutaneous tumor volume by 86% on average](https://bmcmedimaging.biomedcentral.com/articles/10.1186/1471-2342-8-16), and the bias grew as tumors got larger. Caliper readings also varied more between repeat measurements than imaging did.

Those errors matter less than they sound, as long as every group is measured the same way by trained staff. What calipers can't do is see a tumor they can't feel: one in the liver, lung, brain, bone or bladder.

### How bioluminescence imaging works

Bioluminescence imaging (BLI) uses tumor cells engineered to express firefly luciferase. When the animal receives D-luciferin, usually by intraperitoneal injection, living tumor cells emit light that a sensitive camera such as an IVIS Spectrum detects through the body. The total light from a region is a proxy for the number of living tumor cells.

BLI's strengths are the ones calipers lack:

- It sees tumors anywhere in the body, so orthotopic and metastatic models become measurable
- It detects small tumor burdens before a mass is palpable
- It tracks the same animal over time, which can reduce group sizes
- It can image tissues ex vivo at the end of the study to confirm where tumor spread

### What can skew a bioluminescence signal

BLI measures living, oxygenated, luciferase-expressing cells, not tumor mass. Keep these effects in mind when you design a study and read the data:

- **Timing.** Light output rises and falls after each luciferin injection. Run a kinetic curve in each new model and image at the same point on it every session.
- **Necrosis and hypoxia.** The luciferase reaction needs oxygen and ATP, so large tumors with dead or hypoxic cores can emit less light than their size suggests.
- **Depth.** Tissue absorbs and scatters light, so a deep tumor reads dimmer than a shallow one of the same size. Compare animals within the same model, not across models.
- **Reporter stability.** Cells can lose luciferase expression over many passages. Confirm expression in the cell bank before the study.

### Which should your study use?

| Study type | Recommended readout | Why |
| --- | --- | --- |
| Subcutaneous xenograft or syngeneic, single compound | Calipers | Accurate enough for group comparisons, lowest cost |
| Subcutaneous, large dose-ranging study | Calipers, with BLI on a subset | Keeps cost down while confirming viable tumor |
| Orthotopic model (liver, pancreas, brain, bladder) | BLI | The tumor isn't palpable |
| Metastasis or dissemination model | BLI, plus ex vivo imaging at termination | Tracks spread over time and confirms sites |
| Immuno-oncology study with flow cytometry endpoints | Calipers or BLI, by model | Pair with tumor and spleen immunophenotyping |

Many studies combine the two: calipers several times a week for growth curves, and weekly imaging to catch what calipers miss. Whatever you choose, decide it before the study starts and keep the method, operator training and imaging schedule constant throughout.

*Planning an oncology study? See our tumor models or ask an Alpha scientist which readout fits your program.*

---

## Post 3: Diet-induced versus genetic models of type 2 diabetes

| SEO field | Value |
| --- | --- |
| Author | Joan Flanagan, PhD |
| Title tag | Diet-Induced vs Genetic Type 2 Diabetes Models \| Alpha Preclinical |
| Meta description | DIO mice, db/db, ob/ob and ZDF rats each answer different questions. How to choose a type 2 diabetes model for your metabolic drug candidate. |
| Slug | /blog/diet-induced-vs-genetic-type-2-diabetes-models |
| Primary keyword | type 2 diabetes animal models |
| Secondary keywords | diet-induced obesity mouse, db/db mouse, ob/ob mouse, ZDF rat, metabolic disease CRO |
| Internal links | Metabolic disease page, Publications page, Contact page |

Type 2 diabetes in people develops over years from a mix of genes, diet and age. No rodent reproduces all of it, so the right model depends on which part of the disease your drug targets. The first choice is between diet-induced models, which mimic how most human type 2 diabetes begins, and genetic models, which deliver severe disease quickly and uniformly.

### Diet-induced obesity models

The most widely used is the C57BL/6 mouse fed a high-fat diet, typically 45% or 60% of calories from fat. Over several weeks to a few months, these mice gain weight, become insulin resistant and glucose intolerant, and develop fatty liver. Most stay prediabetic rather than developing overt, severe hyperglycemia.

That profile makes diet-induced obesity (DIO) mice a strong choice for drugs aimed at weight loss, insulin sensitivity or fatty liver, especially those meant for early disease. The trade-offs are time, since animals need weeks on diet before dosing, and variability, since not every mouse responds equally.

The details matter. Diet composition, the age diet starts, and even the C57BL/6 substrain change the result: a [comparison of three C57BL/6J-related substrains](https://www.nature.com/articles/s41598-020-70765-w) found they gained weight differently on a western diet and differed in fasting insulin. Specify the substrain and vendor in your protocol and keep them constant across studies.

### Genetic models

Genetic models carry mutations in the leptin pathway, which controls appetite and energy balance. They overeat from weaning and develop disease on a predictable schedule.

- **ob/ob mice** lack leptin. They become severely obese and insulin resistant, but their hyperglycemia is usually mild and transient, because their beta cells compensate.
- **db/db mice** lack a functional leptin receptor. They become obese and progress to overt diabetes as beta cells fail, which makes them useful for glucose-lowering drugs and diabetic complications.
- **Zucker diabetic fatty (ZDF) rats** carry a leptin receptor mutation, and males develop type 2 diabetes. Rats give larger blood volumes for serial sampling and are often preferred for pharmacology and complication studies.

The benefit is speed and consistency. The cost is that leptin deficiency is rare in human patients, so these models exaggerate one cause of a disease that is usually polygenic.

### Rat models for diabetic complications

When the question is a complication such as neuropathy, nephropathy or cardiovascular damage, the model needs sustained diabetes long enough for that damage to develop. Rats are often the better species here.

Alpha's scientists have contributed to this area directly. Joan Flanagan co-authored the description of the BBZDR/Wor rat, a model in which [obese males spontaneously develop type 2 diabetes at about 10 weeks of age](https://link.springer.com/article/10.1186/s12967-020-02428-3), with hyperglycemia, insulin resistance, dyslipidemia and hypertension. Models like this let researchers study complications without the confounding effects of a chemical used to destroy beta cells.

### How to choose

| Your drug aims to | Consider | Why |
| --- | --- | --- |
| Reduce body weight or improve insulin sensitivity | DIO mouse | Mirrors the early, diet-driven disease most patients have |
| Treat fatty liver alongside metabolic disease | DIO mouse on a fatty-liver diet | Develops steatosis over the diet period |
| Lower blood glucose in overt diabetes | db/db mouse or ZDF rat | Reliable, sustained hyperglycemia |
| Protect or restore beta cell function | db/db mouse | Beta cells fail progressively |
| Prevent diabetic complications | Diabetic rat models | Long-lasting disease and larger samples |

Many programs use two models: a DIO model to show the drug works in the context most patients have, and a genetic model to show effect in severe disease. Endpoints such as glucose and insulin tolerance tests, HbA1c, serum chemistry and liver histology can be run on the same animals.

*Developing a metabolic therapy? See our metabolic disease expertise or talk with Joan's team about the right model.*

---

## Post 4: What an mRNA liver depot study looks like in practice

| SEO field | Value |
| --- | --- |
| Author | Barak Yahalom, DVM |
| Title tag | Designing In Vivo mRNA-LNP Studies: Lessons \| Alpha Preclinical |
| Meta description | How preclinical studies of mRNA-LNP protein replacement are designed, using published Fabry disease and hemophilia B studies as worked examples. |
| Slug | /blog/mrna-lnp-liver-depot-in-vivo-study-design |
| Primary keyword | mRNA LNP in vivo study |
| Secondary keywords | mRNA protein replacement therapy, lipid nanoparticle preclinical, gene therapy CRO, knockout mouse model |
| Internal links | Gene therapy page, Publications page, In vitro laboratory service, Contact page |

An mRNA liver depot uses the liver as a factory. Lipid nanoparticles (LNPs) carry mRNA to liver cells after an intravenous dose, the cells make the missing protein, and it circulates through the body. Two published studies co-authored by Alpha's Barak Yahalom show how preclinical programs prove this works, and what a well-designed in vivo study needs.

### The two worked examples

In [hemophilia B](https://www.nature.com/articles/gt201646) (Gene Therapy, 2016), mRNA encoding human factor IX was delivered in LNPs made with the lipidoid C12-200. In factor IX knockout mice, a single dose produced therapeutic factor IX levels and markedly reduced blood loss after a surgical injury.

In [Fabry disease](https://www.sciencedirect.com/science/article/pii/S1525001619300863) (Molecular Therapy, 2019), mRNA encoding human alpha-galactosidase A was delivered the same way to knockout mice lacking the enzyme. A single dose cleared more of the toxic lipid Gb3 from the heart and kidney than enzyme replacement therapy did, and monthly mRNA dosing matched weekly enzyme replacement over two months.

### 1. Use more than one model

Both programs moved through the same sequence of models, each answering a different question:

| Model | Question it answers |
| --- | --- |
| Wild-type mice | How much protein does a dose produce, and for how long? |
| Knockout disease mice | Does the protein correct the disease? |
| Non-human primates | Does expression translate to a larger species? |

Wild-type mice are cheap and plentiful, so they carry the dose-ranging and formulation screening. Knockout mice are the efficacy test. Data from a second species builds confidence before the clinic.

### 2. Measure the protein, not just the outcome

The primary readout of an mRNA study is the protein your mRNA is supposed to make. Plan serial blood samples to build a time course of protein levels after each dose, measured by ELISA or an activity assay.

That time course is what makes the depot concept visible: in the Fabry study, the enzyme made from mRNA circulated far longer than infused enzyme. It also sets the dosing interval for longer studies. Sampling schedules need careful planning in mice, where blood volume limits how often you can draw.

### 3. Choose a disease endpoint that matters clinically

Protein in the blood is necessary but not sufficient. Each study paired it with an endpoint tied to the disease:

- **Hemophilia B:** blood loss after a standardized surgical injury, a direct test of whether clotting is restored
- **Fabry disease:** Gb3 and lyso-Gb3 levels in the tissues that fail in patients, the heart and kidney

Pick the endpoint a clinician or regulator would recognize, and collect the tissues for it at termination.

### 4. Include the standard of care

The Fabry study's most persuasive result is the head-to-head comparison with enzyme replacement therapy. If an approved therapy exists, include it as a comparator arm. Matching or beating the current standard is what investors and partners want to see.

### 5. Plan for repeat dosing and tolerability

mRNA therapy for a chronic disease means repeat doses. Design at least one arm that doses repeatedly, and track body weight, clinical observations, liver enzymes and protein levels across doses. That shows whether expression holds up and whether the LNP is well tolerated over time.

### What this means for your mRNA program

The design principles transfer to most mRNA and LNP programs, from protein replacement to gene editing:

- Screen formulations and doses in wild-type animals first
- Prove efficacy in the disease model with a clinically meaningful endpoint
- Build the protein time course into every study
- Compare against the standard of care where one exists
- Test repeat dosing before you need it

*Developing an mRNA or gene therapy? See our gene therapy expertise and publications, or talk with Barak about study design.*

---

## Post 5: Getting more from every animal with in-house flow cytometry

| SEO field | Value |
| --- | --- |
| Author | Cindy Hopper |
| Title tag | Flow Cytometry in In Vivo Studies: A Practical Guide \| Alpha Preclinical |
| Meta description | How to add immunophenotyping to an in vivo study: which tissues to sample, why fresh samples matter, and how to plan a mouse flow cytometry panel. |
| Slug | /blog/flow-cytometry-immunophenotyping-in-vivo-studies |
| Primary keyword | mouse immunophenotyping flow cytometry |
| Secondary keywords | in vivo flow cytometry CRO, T cell phenotyping mouse, tumor infiltrating lymphocytes, preclinical biomarkers |
| Internal links | In vitro laboratory service, Autoimmune disease page, Tumor models page, Contact page |

Flow cytometry can tell you not just whether a drug worked, but how: which immune cells moved, expanded or switched on. Adding it to an in vivo study costs far less than running a separate study, as long as it's planned before the first animal is dosed.

### Why run flow cytometry in the same building as the study

Flow cytometry works best on fresh cells. Every hour between collection and staining, and every day in transit, costs viability and can shift what you measure: activation markers change, fragile populations die off, and the cleanest gating becomes hard to reproduce.

When the lab sits next to the vivarium, tissues go from necropsy to processing the same day, often within hours. Samples from each timepoint are handled the same way by the same people, which keeps day-to-day variation out of your data. You also avoid courier costs and the risk of a lost shipment on the last day of a study.

### Which samples can you collect?

| Sample | When | What it tells you |
| --- | --- | --- |
| Peripheral blood | Serially, during the study | Changes in circulating immune cells over time |
| Spleen | At termination | Systemic immune response, a large cell yield |
| Lymph nodes | At termination | Responses near the site of disease or dosing |
| Tumor | At termination | Infiltrating lymphocytes and myeloid cells in the tumor |
| Target tissue (lung, liver, joint, gut) | At termination | Inflammation where the disease happens |

Blood is the only sample you can take repeatedly from the same mouse, and the volume is small. If you need several analyses from each draw, plan the panel and sample allocation together.

### Planning a panel

Start from the biological question, not the marker list. "Does our drug increase CD8 T cell infiltration into the tumor?" leads to a focused panel that answers it well. A list of every marker you might want leads to a panel that answers nothing clearly.

Good panels share a few habits:

- **A viability dye**, so dead cells, which bind antibodies nonspecifically, can be excluded
- **An Fc receptor block**, to stop antibodies binding to cells through their tails rather than their targets
- **Bright fluorochromes on rare or dim markers**, and dimmer ones on abundant markers
- **Fluorescence-minus-one controls**, so gates are set objectively
- **A fixed gating strategy**, written down before the study and applied the same way to every sample

### Assays beyond immunophenotyping

Counting cell types is the start. Functional assays show what those cells do:

- **Stimulation assays** restimulate T cells, for example with viral antigens, and measure the cytokines they produce
- **T and B cell phenotyping** separates naive, effector and memory populations
- **Magnetic bead sorting** enriches a cell type for downstream work
- **Cytokine measurement by ELISA** on serum or tissue complements the cellular picture

For autoimmune and immuno-oncology programs, these readouts often show the mechanism behind an efficacy result, which is what partners and investors ask about next.

### A checklist before you add flow to a study

- [ ] Define the question each panel answers
- [ ] Choose tissues and timepoints, and check blood volume limits
- [ ] Confirm sample processing will happen fresh, on site
- [ ] Agree the panel, controls and gating strategy before dosing
- [ ] Decide how data will be analyzed and reported (for example, FlowJo workspaces plus summary tables)

*Planning an immunology readout? See our in vitro laboratory services or ask Cindy's team about panel design.*

---

## Post 6: Continuous dosing with Alzet pumps: when and why

| SEO field | Value |
| --- | --- |
| Author | Gil Chacon |
| Title tag | Alzet Osmotic Pumps for Continuous Dosing in Rodents \| Alpha Preclinical |
| Meta description | When continuous infusion beats daily injections: how Alzet osmotic pumps work, choosing a model, and subcutaneous vs ICV delivery in mice and rats. |
| Slug | /blog/alzet-osmotic-pump-continuous-dosing |
| Primary keyword | Alzet osmotic pump |
| Secondary keywords | continuous subcutaneous infusion mice, intracerebroventricular infusion, ICV dosing rodent, preclinical dosing route |
| Internal links | Surgical services, PK/PD page, Contact page |

Some compounds can't do their job with a daily injection. If a molecule clears in minutes, or the biology needs steady exposure, a once-a-day bolus gives a spike and then nothing. An implanted osmotic pump delivers the compound continuously for days or weeks, without repeated handling of the animal.

### How an osmotic pump works

An [Alzet pump](https://alzet.com/products/alzet_pumps/how-does-it-work/) is a small capsule with a drug reservoir surrounded by a salt layer and a semipermeable outer membrane. Once implanted, water from the surrounding tissue moves into the salt layer, which squeezes the reservoir and pushes the solution out at a steady rate.

The rate is set by the membrane, not by your compound, so delivery is predictable whatever the molecule. You set the dose by changing the concentration in the fill solution. Pumps cover rates from 0.08 to 10 µL per hour and durations from about one day to six weeks, in [three sizes](https://alzet.com/products/ALZET_pumps/specifications/): 100 µL and 200 µL models suited to mice, and 2 mL models for rats and larger animals.

### When continuous dosing is the better choice

- **Short half-life compounds**, such as many peptides, that would need several injections a day to stay above effective levels
- **Steady-state questions**, where the biology depends on constant exposure rather than peaks and troughs
- **Long studies**, where daily injections would stress the animals and add handling variables
- **Disease induction**, where an agent such as a hormone or peptide is infused to create the model itself
- **Brain delivery**, where a compound that can't cross the blood-brain barrier is infused directly into the brain

Daily injection is still simpler and cheaper when exposure from a bolus is enough, and it lets you stop dosing at any time. A pump keeps delivering until it's empty or removed.

### Subcutaneous, intraperitoneal or brain delivery

| Route | How | Used for |
| --- | --- | --- |
| Subcutaneous | Pump placed under the skin of the back through a small incision | Most systemic dosing, the simplest surgery |
| Intraperitoneal | Pump placed in the abdominal cavity | Larger pumps in small animals, or faster uptake for some compounds |
| Intravenous | Pump connected to a catheter in a vein | Compounds that must reach blood directly |
| Intracerebroventricular (ICV) | Pump under the skin, connected by a catheter to a cannula placed in a brain ventricle | Delivery to the brain past the blood-brain barrier |

Every route is a surgery. Aseptic technique, anesthesia, pain relief and post-operative monitoring matter as much as the pump itself, and ICV placement needs stereotaxic surgery to hit the ventricle reliably.

### Five details that decide whether pump studies work

1. **Stability at body temperature.** The compound sits at 37 °C for the whole delivery period. Confirm it stays stable and soluble for that long in your vehicle.
2. **Solubility at the needed concentration.** The pump's rate is fixed, so a high dose needs a concentrated solution. Check it can be made before you choose the model.
3. **Priming.** A pump takes several hours to reach its steady rate after implantation. When delivery must start immediately, prime filled pumps in warm saline beforehand.
4. **Fill carefully.** Air bubbles in the reservoir cause uneven delivery. Weigh pumps before and after filling to confirm the volume.
5. **Check delivery at the end.** Recover pumps at termination and measure the residual volume, and confirm exposure with a plasma sample where you can.

*Need continuous or brain-targeted dosing? See our surgical services or talk with our team about pump selection.*

---

## Post 7: Spontaneous rat models of type 1 diabetes, explained

| SEO field | Value |
| --- | --- |
| Author | Joan Flanagan, PhD |
| Title tag | Rat Models of Type 1 Diabetes: BB, LEW.1WR1 & More \| Alpha Preclinical |
| Meta description | BB, LEW.1WR1 and KDP rats model autoimmune type 1 diabetes in different ways. How they work, how disease is triggered, and when to use a rat over the NOD mouse. |
| Slug | /blog/rat-models-type-1-diabetes |
| Primary keyword | type 1 diabetes rat model |
| Secondary keywords | LEW.1WR1 rat, BB rat diabetes, autoimmune diabetes model, NOD mouse alternative |
| Internal links | Autoimmune disease page, Publications page, Contact page |

Most type 1 diabetes research uses the NOD mouse. Rat models answer questions the mouse can't: they develop disease by different genetic routes, some can be triggered on demand by a virus, and their size makes surgery and serial sampling easier. Alpha's scientists have published on several of them.

### Why use a rat?

Type 1 diabetes is an autoimmune attack on insulin-producing beta cells. NOD mice model it well, but a drug that works in one strain may be acting on that strain's particular genetics. Confirming an effect in a rat model, which reaches the disease by another route, makes the result more convincing.

Rats also offer practical advantages: larger blood volumes for repeated sampling, easier surgery, and pancreas samples large enough for detailed analysis.

### The main models

| Model | How diabetes arises | Best for |
| --- | --- | --- |
| BB diabetes-prone rat | Spontaneously, in a strain with a T cell deficiency (lymphopenia) | Classic spontaneous disease and long-term complications |
| BB diabetes-resistant (BBDR) rat | Doesn't develop diabetes on its own, but can be triggered by viral infection or immune stimulation | Studying how environmental triggers start autoimmunity |
| LEW.1WR1 rat | Rarely on its own; readily after viral infection or immune stimulation | Gene-environment studies and prevention therapies on a predictable schedule |
| KDP rat | Spontaneously, without lymphopenia | Autoimmunity driven by a different genetic pathway |

These descriptions follow [a published review of rat models of type 1 diabetes](https://link.springer.com/protocol/10.1007/978-1-0716-0385-7_5).

### LEW.1WR1: diabetes you can trigger

Joan Flanagan co-authored a paper describing diabetes in the LEW.1WR1 rat. A small fraction of these rats develop autoimmune diabetes spontaneously, but many more do after an environmental perturbation, such as infection with Kilham rat virus or treatment with the immune stimulant poly I:C.

That makes LEW.1WR1 rats valuable in two ways. They model how an infection might set off type 1 diabetes in a genetically susceptible person, which is one of the leading hypotheses for how the human disease begins. And because researchers control when the trigger happens, prevention studies can be timed precisely rather than waiting for random spontaneous onset.

### Using these models to test therapies

Rat models have been used to test immune-targeted prevention strategies. Barak Yahalom co-authored a study showing that an antibody against T cells carrying a particular receptor variant, Vβ13, prevented diabetes in rat models, pointing to that receptor as both a therapeutic target and a biomarker.

For longer studies of established diabetes, animals need insulin to stay healthy. Alpha's scientists also co-authored work on standardized insulin treatment protocols for spontaneous rodent models, so long-term studies of complications can run consistently across labs.

### Choosing a type 1 diabetes model

- **Testing whether a therapy prevents disease onset?** An inducible model such as LEW.1WR1 or BBDR lets you time treatment to the trigger.
- **Testing whether a therapy reverses established disease?** Spontaneous models with defined onset, followed by careful glucose monitoring, are the usual choice.
- **Studying complications?** Plan for insulin support and a study long enough for damage to develop.
- **Confirming a result from NOD mice?** A rat model with a different genetic route strengthens the case.

Model availability changes as colonies are retired or moved, so confirm the source and current characterization of any strain before you plan around it.

*Working on an autoimmune therapy? See our autoimmune disease expertise or talk with Joan about the right model.*

---

## Sources

External sources cited in the posts, opened October 2026:

- [Tumor volume in subcutaneous mouse xenografts measured by microCT](https://bmcmedimaging.biomedcentral.com/articles/10.1186/1471-2342-8-16), BMC Medical Imaging
- [The ARRIVE guidelines 2.0](https://arriveguidelines.org/arrive-guidelines), NC3Rs
- [C57BL/6J substrain differences in response to high-fat diet intervention](https://www.nature.com/articles/s41598-020-70765-w), Scientific Reports
- [Evaluating blood-brain barrier permeability in a rat model of type 2 diabetes](https://link.springer.com/article/10.1186/s12967-020-02428-3) (BBZDR/Wor description), Journal of Translational Medicine
- [Therapeutic efficacy in a hemophilia B model using a biosynthetic mRNA liver depot system](https://www.nature.com/articles/gt201646), Gene Therapy, 2016
- [Improved efficacy in a Fabry disease model using a systemic mRNA liver depot system](https://www.sciencedirect.com/science/article/pii/S1525001619300863), Molecular Therapy, 2019
- [How ALZET pumps work](https://alzet.com/products/alzet_pumps/how-does-it-work/) and [pump specifications](https://alzet.com/products/ALZET_pumps/specifications/), ALZET
- [Rat models of human type 1 diabetes](https://link.springer.com/protocol/10.1007/978-1-0716-0385-7_5), Methods in Molecular Biology

PubMed and two PMC articles could not be opened while writing. Statements about bioluminescence imaging limits and the LEW.1WR1 and Vβ13 studies reflect established literature and the Alpha publications list; the authors should confirm them.
