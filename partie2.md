# Partie 2 : Orchestration avec Kubernetes

## 1. Préparation du cluster

```bash
> minikube start

😄  minikube v1.37.0 on Fedora 42
✨  Automatically selected the docker driver. Other choices: qemu2, none, ssh
📌  Using Docker driver with root privileges
👍  Starting "minikube" primary control-plane node in "minikube" cluster
🚜  Pulling base image v0.0.48 ...
💾  Downloading Kubernetes v1.34.0 preload ...
    > gcr.io/k8s-minikube/kicbase...:  488.52 MiB / 488.52 MiB  100.00% 26.10 M
    > preloaded-images-k8s-v18-v1...:  337.07 MiB / 337.07 MiB  100.00% 13.72 M
🔥  Creating docker container (CPUs=2, Memory=3900MB) ...
🐳  Preparing Kubernetes v1.34.0 on Docker 28.4.0 ...
🔗  Configuring bridge CNI (Container Networking Interface) ...
🔎  Verifying Kubernetes components...
    ▪ Using image gcr.io/k8s-minikube/storage-provisioner:v5
🌟  Enabled addons: storage-provisioner, default-storageclass
🏄  Done! kubectl is now configured to use "minikube" cluster and "default" namespace by default
```

```bash
> kubectl config use-context minikube

Switched to context "minikube".
```

```bash
> kubectl get nodes

NAME       STATUS   ROLES           AGE     VERSION
minikube   Ready    control-plane   3h45m   v1.34.0
```

## 2. Fichiers de déploiement

**`deployment.yml`**

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: php-app
spec:
  replicas: 1
  selector:
    matchLabels:
      app: gestion-produits-php
  template:
    metadata:
      labels:
        app: gestion-produits-php
    spec:
      containers:
      - name: php-app
        image: periicles/gestion-produits-php:latest
        ports:
        - containerPort: 80
        env:
        - name: MYSQL_HOST
          value: gestion-produits-db
        - name: MYSQL_DATABASE
          value: gestion_produits
        - name: MYSQL_USER
          value: root
        - name: MYSQL_PASSWORD
          value: root
        resources:
          requests:
            memory: "128Mi"
            cpu: "100m"
          limits:
            memory: "256Mi"
            cpu: "500m"
        volumeMounts:
        - name: uploads-storage
          mountPath: /var/www/html/uploads
      volumes:
      - name: uploads-storage
        persistentVolumeClaim:
          claimName: uploads-pvc

---
apiVersion: apps/v1
kind: Deployment
metadata:
  name: gestion-produits-db
spec:
  replicas: 1
  selector:
    matchLabels:
      app: gestion-produits-db
  template:
    metadata:
      labels:
        app: gestion-produits-db
    spec:
      containers:
      - name: mysql
        image: mysql:8.0
        env:
        - name: MYSQL_ROOT_PASSWORD
          value: root
        - name: MYSQL_DATABASE
          value: gestion_produits
        ports:
        - containerPort: 3306
        resources:
          requests:
            memory: "256Mi"
            cpu: "250m"
          limits:
            memory: "512Mi"
            cpu: "1000m"
        volumeMounts:
        - name: mysql-persistent-storage
          mountPath: /var/lib/mysql
      volumes:
      - name: mysql-persistent-storage
        persistentVolumeClaim:
          claimName: mysql-pvc
```

**`persistent-volumes.yml`**

```yaml
apiVersion: v1
kind: PersistentVolumeClaim
metadata:
  name: mysql-pvc
spec:
  accessModes:
    - ReadWriteOnce
  resources:
    requests:
      storage: 5Gi
---
apiVersion: v1
kind: PersistentVolumeClaim
metadata:
  name: uploads-pvc
spec:
  accessModes:
    - ReadWriteOnce
  resources:
    requests:
      storage: 2Gi
```

**`service.yml`**

```yaml
apiVersion: v1
kind: Service
metadata:
  name: php-app-service
spec:
  selector:
    app: gestion-produits-php
  type: NodePort
  ports:
  - protocol: TCP
    port: 80
    targetPort: 80
    nodePort: 30080

---
apiVersion: v1
kind: Service
metadata:
  name: gestion-produits-db-service
spec:
  selector:
    app: gestion-produits-db
  ports:
  - protocol: TCP
    port: 3306
    targetPort: 3306
  clusterIP: None
```

## 3. Commandes utilisées

```bash
> kubectl apply -f persistent-volumes.yml

persistentvolumeclaim/mysql-pvc created
persistentvolumeclaim/uploads-pvc created
```

```bash
> kubectl apply -f deployment.yml

deployment.apps/php-app created
deployment.apps/gestion-produits-db created
```

```bash
> kubectl apply -f service.yml

service/php-app-service created
Warning: spec.SessionAffinity is ignored for headless services
service/gestion-produits-db-service created
```

```bash
> kubectl get pods

NAME                                   READY   STATUS    RESTARTS   AGE
gestion-produits-db-6668b94fb5-9qp7c   1/1     Running   0          76s
php-app-575dd855c8-msnvw               1/1     Running   0          76s
```

```bash
> kubectl scale deployment php-app --replicas=3

deployment.apps/php-app scaled
```

```bash
> kubectl get pods

NAME                                   READY   STATUS    RESTARTS   AGE
gestion-produits-db-6668b94fb5-9qp7c   1/1     Running   0          42m
php-app-575dd855c8-vg4hn               1/1     Running   0          42m
php-app-575dd855c8-w4ftx               1/1     Running   0          42m
php-app-575dd855c8-msnvw               1/1     Running   0          48m
```

```bash
> minikube service php-app-service


┌───────────┬─────────────────┬─────────────┬───────────────────────────┐
│ NAMESPACE │      NAME       │ TARGET PORT │            URL            │
├───────────┼─────────────────┼─────────────┼───────────────────────────┤
│ default   │ php-app-service │ 80          │ http://192.168.49.2:30080 │
└───────────┴─────────────────┴─────────────┴───────────────────────────┘
🎉  Opening service default/php-app-service in default browser...
```

## 4. Sorties types prouvant le déploiement et scalabilité

Pods en état Running avec plusieurs réplicas

```bash
> kubectl get pods

NAME                                   READY   STATUS    RESTARTS   AGE
gestion-produits-db-6668b94fb5-9qp7c   1/1     Running   0          42m
php-app-575dd855c8-vg4hn               1/1     Running   0          42m
php-app-575dd855c8-w4ftx               1/1     Running   0          42m
php-app-575dd855c8-msnvw               1/1     Running   0          48m
```

Service exposé

```bash
> kubectl get svc

NAME                          TYPE        CLUSTER-IP     EXTERNAL-IP   PORT(S)        AGE
gestion-produits-db-service   ClusterIP   None           <none>        3306/TCP       49m
kubernetes                    ClusterIP   10.96.0.1      <none>        443/TCP        4h39m
php-app-service               NodePort    10.108.4.177   <none>        80:30080/TCP   49m
```
